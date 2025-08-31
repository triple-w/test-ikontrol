<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\RfcCsd;
use App\Models\RfcUsuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SellosController extends Controller
{
    public function index()
    {
        $rfcStr = session('rfc_seleccionado');
        abort_unless($rfcStr, 422, 'Selecciona un RFC activo desde el menú.');

        $rfcUsuario = RfcUsuario::where('rfc', $rfcStr)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $csds = $rfcUsuario->csds()->latest()->get();

        return view('configuracion/sellos/index', compact('rfcUsuario','csds'));
    }

    public function store(Request $request)
        {
            $rfcStr = session('rfc_seleccionado');
            abort_unless($rfcStr, 422, 'Selecciona un RFC activo desde el menú.');

            $rfcUsuario = \App\Models\RfcUsuario::where('rfc', $rfcStr)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            // Validación tolerante de tipos
            $data = $request->validate([
                'cer' => [
                    'required', 'file',
                    'mimetypes:application/x-x509-ca-cert,application/pkix-cert,application/octet-stream,application/x-x509-user-cert,application/x-x509-ca-cert',
                    function ($attr, $file, $fail) {
                        if (!$file) return;
                        $name = strtolower($file->getClientOriginalName());
                        if (!Str::endsWith($name, '.cer')) {
                            $fail('El archivo debe tener extensión .cer');
                        }
                    },
                ],
                'key' => [
                    'required', 'file',
                    'mimetypes:application/octet-stream,application/x-pem-file,application/pkcs8,text/plain',
                    function ($attr, $file, $fail) {
                        if (!$file) return;
                        $name = strtolower($file->getClientOriginalName());
                        if (!Str::endsWith($name, '.key')) {
                            $fail('El archivo debe tener extensión .key');
                        }
                    },
                ],
                'password' => ['required','string','max:255'],
            ]);

            // === Variables inicializadas para evitar "Undefined variable" ===
            $disk = config('filesystems.disks.private') ? 'private' : 'local';
            $cerPath = null;
            $keyPath = null;

            // Guardar archivos
            $baseDir = "rfcs/{$rfcUsuario->id}/csd/".Str::random(6)."_".now()->format('YmdHis');
            $timestamp = now()->format('Ymd_His');
            $cerPath = $request->file('cer')->storeAs($baseDir, "csd_{$timestamp}.cer", $disk);
            $keyPath = $request->file('key')->storeAs($baseDir, "csd_{$timestamp}.key", $disk);

            // Validación remota
            $endpoint = "https://app.totalnot.mx/validador/api/valida.php";
            try {
                $keyStream = fopen(Storage::disk($disk)->path($keyPath), 'r');
                $cerStream = fopen(Storage::disk($disk)->path($cerPath), 'r');

                Log::info('CSD VALIDATOR: POST', [
                    'endpoint' => $endpoint,
                    'rfc' => $rfcUsuario->rfc,
                    'cer' => $cerPath,
                    'key' => $keyPath,
                ]);

                $response = Http::withoutVerifying()
                    ->attach('key', $keyStream, basename($keyPath))
                    ->attach('cer', $cerStream, basename($cerPath))
                    ->asMultipart()
                    ->post($endpoint, [
                        'rfc'   => $rfcUsuario->rfc,
                        'clave' => $data['password'],
                    ]);

                $debug = [
                    'status'  => $response->status(),
                    'headers' => $response->headers(),
                    'body'    => $response->body(),
                ];
                Log::info('CSD VALIDATOR: RESPONSE', $debug);

            } catch (\Throwable $e) {
                $this->deleteIfExists($disk, [$cerPath, $keyPath]);
                Log::error('CSD VALIDATOR: EXCEPTION', ['msg' => $e->getMessage()]);
                return back()->withErrors(['cer' => 'No se pudo contactar el validador: '.$e->getMessage()]);
            }

            if ($response->failed()) {
                $this->deleteIfExists($disk, [$cerPath, $keyPath]);
                return back()
                ->with('error', 'Error al contactar el validador de CSD.')
                ->with('api_debug', $debug);
            }

            $json = json_decode($response->body());
            if (isset($json->isError) && $json->isError) {
                $this->deleteIfExists($disk, [$cerPath, $keyPath]);
                return back()
                ->with('error', $json->texto ?? 'El validador rechazó los archivos.')
                ->with('api_debug', ['status' => $response->status(), 'json' => $json]);
            }

            // Extraer datos
            $noCert    = $json->csd_serie       ?? null;
            $vigHasta  = $json->vigencia_hasta  ?? null;
            $nombre    = $json->name            ?? null;
            $vigDesde  = $json->vigencia_desde  ?? null;

            // PEMs (si falla, seguimos)
            [$cerPemPath, $keyPemPath] = $this->generarPems($cerPath, $keyPath, $data['password'], $baseDir, $disk);
            
            $warn = null;
            if (!$cerPemPath) {
                $warn = 'No se pudo generar el CERT.PEM (cer).';
            }
            if (!$keyPemPath) {
                $warn = trim(($warn ? $warn.' ' : '').'No se pudo generar el KEY.PEM (key). Revisa que OpenSSL esté instalado y que shell_exec no esté deshabilitado.');
            }

            // Guardar CSD
            $csd = \App\Models\RfcCsd::create([
                'rfc_usuario_id'   => $rfcUsuario->id,
                'nombre'           => $nombre,
                'no_certificado'   => $noCert,
                'vigencia_desde'   => $vigDesde ? date('Y-m-d', strtotime($vigDesde)) : null,
                'vigencia_hasta'   => $vigHasta ? date('Y-m-d', strtotime($vigHasta)) : null,
                'cer_path'         => $cerPath,
                'key_path'         => $keyPath,
                'cer_pem_path'     => $cerPemPath,
                'key_pem_path'     => $keyPemPath,
                'key_password_enc' => encrypt($data['password']),
                'validado'         => true,
                'revisado'         => true,
                'activo'           => false,
            ]);

            if (empty($rfcUsuario->razon_social) && $nombre) {
                $rfcUsuario->update(['razon_social' => $nombre]);
            }

            return back()->with('ok','CSD validado y registrado. Ahora puedes activarlo.')
            ->with('warn', $warn);
        }


    public function activar(RfcCsd $csd)
    {
        // Seguridad: que pertenezca al usuario y al RFC de sesión
        $rfcStr = session('rfc_seleccionado');
        abort_unless($rfcStr, 422, 'Selecciona un RFC activo desde el menú.');

        $rfcUsuario = RfcUsuario::where('rfc', $rfcStr)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        abort_unless($csd->rfc_usuario_id === $rfcUsuario->id, 403);

        // Desactivar los demás y activar éste
        RfcCsd::where('rfc_usuario_id', $rfcUsuario->id)->update(['activo' => false]);
        $csd->update(['activo' => true]);

        $disk = config('filesystems.disks.private') ? 'private' : 'local';

        if (empty($csd->cer_pem_path) || empty($csd->key_pem_path)) {
            $baseDir = dirname($csd->cer_path); // carpeta donde viven cer/key
            try {
                $password = $csd->key_password_enc ? decrypt($csd->key_password_enc) : '';
            } catch (\Throwable $e) {
                $password = '';
            }

            [$cerPem, $keyPem] = $this->generarPems(
                $csd->cer_path,
                $csd->key_path,
                $password,
                $baseDir,
                $disk
            );

            if ($cerPem && $keyPem) {
                $csd->update(['cer_pem_path' => $cerPem, 'key_pem_path' => $keyPem]);
            } else {
                return back()->with('error', 'CSD activado, pero no se pudieron generar los .PEM. Verifica OpenSSL/shell_exec.');
            }
        }

        return back()->with('ok', 'CSD activado para timbrar.');
    }

    public function destroy(RfcCsd $csd)
    {
        $rfcStr = session('rfc_seleccionado');
        abort_unless($rfcStr, 422, 'Selecciona un RFC activo desde el menú.');

        $rfcUsuario = RfcUsuario::where('rfc', $rfcStr)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        abort_unless($csd->rfc_usuario_id === $rfcUsuario->id, 403);

        // Borrar archivos
        $paths = array_filter([$csd->cer_path, $csd->key_path, $csd->cer_pem_path, $csd->key_pem_path]);
        Storage::disk('private')->delete($paths);

        $csd->delete();

        return back()->with('ok', 'CSD eliminado.');
    }

    private function deleteIfExists(string $disk, array $paths): void
    {
        $paths = array_filter($paths);
        if ($paths) {
            Storage::disk($disk)->delete($paths);
        }
    }


    private function generarPems(string $cerPath, string $keyPath, string $password, string $baseDir, string $disk): array
    {
        try {
            // Rutas absolutas
            $cerFull = Storage::disk($disk)->path($cerPath);
            $keyFull = Storage::disk($disk)->path($keyPath);

            // Rutas relativas/absolutas destino
            $cerPemRel = $baseDir.'/cert.pem';
            $keyPemRel = $baseDir.'/key.pem';

            $cerPemAbs = Storage::disk($disk)->path($cerPemRel);
            $keyPemAbs = Storage::disk($disk)->path($keyPemRel);

            @mkdir(dirname($cerPemAbs), 0775, true);
            @mkdir(dirname($keyPemAbs), 0775, true);

            // --- CER -> PEM (sin openssl: DER -> base64 + headers) ---
            $der = @file_get_contents($cerFull);
            if ($der === false) {
                throw new \RuntimeException('No se pudo leer el .cer');
            }
            $pemCert = "-----BEGIN CERTIFICATE-----\n"
                    . chunk_split(base64_encode($der), 64, "\n")
                    . "-----END CERTIFICATE-----\n";

            if (@file_put_contents($cerPemAbs, $pemCert) === false) {
                throw new \RuntimeException('No se pudo escribir cert.pem');
            }

            // --- KEY -> PEM (requiere openssl para desencriptar PKCS#8 DER) ---
            $opensslOk = function_exists('shell_exec');
            if ($opensslOk) {
                $cmdKey = sprintf(
                    'openssl pkcs8 -inform DER -in %s -out %s -passin pass:%s 2>&1',
                    escapeshellarg($keyFull),
                    escapeshellarg($keyPemAbs),
                    escapeshellarg($password)
                );
                $out = shell_exec($cmdKey);

                // Verifica que efectivamente se haya creado el archivo
                if (!file_exists($keyPemAbs) || filesize($keyPemAbs) < 64) {
                    // Falla: borra basura si quedó y retorna null
                    @unlink($keyPemAbs);
                    return [$cerPemRel, null];
                }
            } else {
                // Sin shell_exec no podemos convertir KEY de forma segura aquí
                return [$cerPemRel, null];
            }

            return [$cerPemRel, $keyPemRel];

        } catch (\Throwable $e) {
            \Log::warning('PEM generation failed', ['err' => $e->getMessage()]);
            return [null, null];
        }
    }


}
