<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TimbradorXpress
{
    protected string $baseUrl;
    protected string $apikey;
    protected string $defaultPdfTemplate;

    public function __construct()
    {
        $s = config('services.timbradorxpress');
        $this->baseUrl           = rtrim($s['base_url'] ?? 'https://dev.timbradorxpress.mx', '/');
        $this->apikey            = $s['apikey'] ?? '';
        $this->defaultPdfTemplate= (string)($s['pdf_template'] ?? '1');
    }

    /**
     * Timbrar con timbrarJSON2 (regresa XML timbrado + PDF base64).
     *
     * @param array $payload           // tu payload (comprobante + conceptos + impuestos + relacionados + uso_cfdi)
     * @param int   $rfcUsuarioId      // RFC activo (id) para leer emisor/CSD
     * @param int   $clienteId         // id del receptor en 'clientes'
     * @param string|null $pdfTemplate // si no llega, usa default (config o BD cuando lo tengas)
     * @return array [uuid, xml, pdf_b64]
     * @throws RuntimeException
     */
    public function timbrarJSON2(array $payload, int $rfcUsuarioId, int $clienteId, ?string $pdfTemplate = null): array
    {
        // 1) Emisor (perfil fiscal) desde rfc_usuarios
        $emisor = DB::table('rfc_usuarios')->where('id', $rfcUsuarioId)->first();
        if (!$emisor) {
            throw new RuntimeException('No se encontró el emisor (rfc_usuarios).');
        }

        // 2) Receptor desde clientes
        $receptor = DB::table('clientes')->where('id', $clienteId)->first();
        if (!$receptor) {
            throw new RuntimeException('No se encontró el cliente receptor.');
        }

        // 3) CSD activo para este RFC
        $csd = DB::table('rfc_csds')
            ->where('rfc_usuario_id', $rfcUsuarioId)
            ->where('activo', 1)
            ->orderByDesc('id')
            ->first();

        if (!$csd) {
            throw new RuntimeException('No hay CSD activo para el RFC seleccionado.');
        }

        // 4) Leer PEMs (asumimos rutas relativas a storage/app)
        $keyPath = storage_path('app/' . ltrim($csd->key_pem_path, '/'));
        $cerPath = storage_path('app/' . ltrim($csd->cer_pem_path, '/'));

        if (!is_file($keyPath) || !is_readable($keyPath)) {
            throw new RuntimeException('No se puede leer keyPEM: ' . $keyPath);
        }
        if (!is_file($cerPath) || !is_readable($cerPath)) {
            throw new RuntimeException('No se puede leer cerPEM: ' . $cerPath);
        }

        $keyPem = file_get_contents($keyPath);
        $cerPem = file_get_contents($cerPath);
        $pass   = (string) ($csd->key_password_enc ?? ''); // nos confirmaste: viene en texto plano

        // 5) Armar CFDI 4.0 en JSON (estructura estándar)
        $cfdi = $this->buildCfdiJson($payload, $emisor, $receptor);

        // 6) Base64 del JSON
        $jsonB64 = base64_encode(json_encode($cfdi, JSON_UNESCAPED_UNICODE));

        // 7) Llamar al PAC (REST timbrarJSON2)
        $endpoint = $this->baseUrl . '/api/rest/servicio/timbrarJSON2';

        $req = [
            'apikey'  => $this->apikey,
            'jsoncfdi'=> $jsonB64,       // algunos docs usan 'jsonB64' o 'jsoncfdi' — si tu PAC exige clave distinta, avísame y lo cambio
            'keyPEM'  => $keyPem,
            'cerPEM'  => $cerPem,
            'passKey' => $pass,
            'pdf'     => $pdfTemplate ?: $this->defaultPdfTemplate,
        ];

        // Usamos Http (Laravel 8+) o Guzzle si estás en 5.x — si Http no existe, cambiamos a Guzzle
        if (!class_exists(\Illuminate\Support\Facades\Http::class)) {
            return $this->callWithGuzzle($endpoint, $req);
        }

        $resp = Http::timeout(30)->asForm()->post($endpoint, $req);

        if (!$resp->ok()) {
            throw new RuntimeException('PAC HTTP error: ' . $resp->status() . ' ' . $resp->body());
        }

        $data = $resp->json();
        if (!$data) {
            throw new RuntimeException('PAC respondió sin JSON válido: ' . $resp->body());
        }

        // Normaliza llaves esperadas (ajústalo si tu PAC usa nombres distintos)
        $uuid   = $data['uuid']   ?? $data['UUID']   ?? null;
        $xml    = $data['xml']    ?? $data['xmlTimbrado'] ?? null;
        $pdfB64 = $data['pdfBase64'] ?? $data['pdf'] ?? null;

        if (!$uuid || !$xml) {
            // Intenta leer mensaje de error estándar
            $err = $data['message'] ?? $data['mensaje'] ?? 'Respuesta inválida del PAC.';
            throw new RuntimeException($err);
        }

        return ['uuid' => $uuid, 'xml' => $xml, 'pdf_b64' => $pdfB64];
    }

    protected function callWithGuzzle(string $endpoint, array $form): array
    {
        $client = new \GuzzleHttp\Client(['timeout' => 30]);
        $resp = $client->post($endpoint, ['form_params' => $form]);

        $code = $resp->getStatusCode();
        $body = (string) $resp->getBody();

        if ($code < 200 || $code >= 300) {
            throw new RuntimeException('PAC HTTP error: ' . $code . ' ' . $body);
        }

        $data = json_decode($body, true);
        if (!$data) {
            throw new RuntimeException('PAC respondió sin JSON válido: ' . $body);
        }

        $uuid   = $data['uuid']   ?? $data['UUID']   ?? null;
        $xml    = $data['xml']    ?? $data['xmlTimbrado'] ?? null;
        $pdfB64 = $data['pdfBase64'] ?? $data['pdf'] ?? null;

        if (!$uuid || !$xml) {
            $err = $data['message'] ?? $data['mensaje'] ?? 'Respuesta inválida del PAC.';
            throw new RuntimeException($err);
        }

        return ['uuid' => $uuid, 'xml' => $xml, 'pdf_b64' => $pdfB64];
    }

    /**
     * Construye el objeto JSON CFDI 4.0 esperado por el PAC a partir de tu payload/BD.
     * Ajusta nombres si tu PAC exigiera claves distintas.
     */
    protected function buildCfdiJson(array $payload, $emisor, $receptor): array
    {
        $tipo = $payload['tipo_comprobante'] ?? 'I'; // I|E
        $moneda = $payload['moneda'] ?? 'MXN';
        $exportacion = $payload['exportacion'] ?? '01';
        $usoCfdi = $payload['uso_cfdi'] ?? null; // ← VIENE desde "Datos del comprobante"

        if (!$usoCfdi) {
            throw new RuntimeException('Falta uso_cfdi en los datos del comprobante.');
        }

        // Emisor (de rfc_usuarios)
        $emisorJson = [
            'Rfc'           => $emisor->rfc,
            'Nombre'        => $emisor->razon_social,
            'RegimenFiscal' => (string)$emisor->regimen_fiscal,     // p.ej. 601
        ];

        // Receptor (de clientes + uso_cfdi del comprobante)
        $receptorJson = [
            'Rfc'                        => $receptor->rfc,
            'Nombre'                     => $receptor->razon_social,
            'UsoCFDI'                    => $usoCfdi,                // p.ej. G03
            'DomicilioFiscalReceptor'    => (string)$receptor->codigo_postal,
            'RegimenFiscalReceptor'      => (string)$receptor->regimen_fiscal, // p.ej. 601
        ];

        // Comprobante
        $comprobante = [
            'Version'           => '4.0',
            'Serie'             => (string)($payload['serie'] ?? ''),
            'Folio'             => (string)($payload['folio'] ?? ''),
            'Fecha'             => $payload['fecha'] ?? now()->format('Y-m-d\TH:i:s'),
            'SubTotal'          => 0,
            'Descuento'         => 0,
            'Moneda'            => $moneda,
            'Total'             => 0,
            'TipoDeComprobante' => $tipo,                // I|E
            'Exportacion'       => $exportacion,         // 01
            'LugarExpedicion'   => (string)$emisor->cp_expedicion,
        ];

        // Opcionales de pago
        if (!empty($payload['forma_pago']))  { $comprobante['FormaPago']  = (string)$payload['forma_pago']; }
        if (!empty($payload['metodo_pago'])) { $comprobante['MetodoPago'] = (string)$payload['metodo_pago']; }

        // Conceptos
        $conceptos = [];
        $subtotal = 0.0; $descuento = 0.0; $impTotal = 0.0;

        foreach (($payload['conceptos'] ?? []) as $c) {
            $cantidad = (float)($c['cantidad'] ?? 0);
            $valorU   = (float)($c['precio'] ?? 0);
            $des      = (float)($c['descuento'] ?? 0);
            $importe  = max($cantidad * $valorU - $des, 0);

            $objetoImp = '01'; // No objeto de impuesto
            $traslados = [];
            $retenciones = [];

            if (!empty($c['impuestos']) && is_array($c['impuestos'])) {
                // Si hay impuestos, ObjetoImp=02
                $objetoImp = '02';

                // Base para impuestos
                $base = max($cantidad * $valorU - $des, 0);

                foreach ($c['impuestos'] as $i) {
                    $factor = $i['factor'] ?? '';
                    if ($factor === 'Exento') { $objetoImp = '01'; continue; } // Exento ⇒ en CFDI a veces se declara con ObjetoImp=01

                    $tipo = $i['tipo'] ?? 'T';          // T (traslado) / R (retención)
                    $imp  = strtoupper($i['impuesto'] ?? 'IVA'); // IVA para mapear 002
                    $tasa = (float)($i['tasa'] ?? 0) / 100.0;

                    $impuestoClave = $imp === 'IVA' ? '002' : ($imp === 'ISR' ? '001' : '003'); // IVA, ISR, IEPS
                    $tasa6 = number_format($tasa, 6, '.', '');
                    $importeImp = round($base * $tasa, 2);

                    $row = [
                        'Base'       => round($base, 2),
                        'Impuesto'   => $impuestoClave,
                        'TipoFactor' => 'Tasa',
                        'TasaOCuota' => $tasa6,
                        'Importe'    => $importeImp,
                    ];

                    if ($tipo === 'R') {
                        $retenciones[] = $row;
                        $impTotal -= $importeImp;
                    } else {
                        $traslados[] = $row;
                        $impTotal += $importeImp;
                    }
                }
            }

            $conceptoJson = [
                'ClaveProdServ' => (string)($c['clave_prod_serv'] ?? ''),
                'Cantidad'      => $cantidad,
                'ClaveUnidad'   => (string)($c['clave_unidad'] ?? ''),
                'Unidad'        => (string)($c['unidad'] ?? ''),
                'Descripcion'   => (string)($c['descripcion'] ?? ''),
                'ValorUnitario' => round($valorU, 2),
                'Importe'       => round($cantidad * $valorU, 2),
                'Descuento'     => round($des, 2),
                'ObjetoImp'     => $objetoImp,
            ];

            if (!empty($traslados) || !empty($retenciones)) {
                $conceptoJson['Impuestos'] = [];
                if (!empty($traslados))   { $conceptoJson['Impuestos']['Traslados']   = $traslados; }
                if (!empty($retenciones)) { $conceptoJson['Impuestos']['Retenciones'] = $retenciones; }
            }

            $conceptos[] = $conceptoJson;

            $subtotal  += ($cantidad * $valorU);
            $descuento += $des;
        }

        $comprobante['SubTotal']  = round($subtotal, 2);
        if ($descuento > 0) { $comprobante['Descuento'] = round($descuento, 2); }
        $comprobante['Total']     = round($subtotal - $descuento + $impTotal, 2);

        $json = [
            'Comprobante' => $comprobante,
            'Emisor'      => $emisorJson,
            'Receptor'    => $receptorJson,
            'Conceptos'   => $conceptos,
        ];

        // CfdiRelacionados
        $rels = $payload['relacionados'] ?? [];
        if (is_array($rels) && count($rels)) {
            $tipoRelacion = (string)($rels[0]['tipo_relacion'] ?? '');
            $uuids = [];
            foreach ($rels as $r) {
                if (!empty($r['uuid'])) $uuids[] = ['UUID' => $r['uuid']];
            }
            if ($tipoRelacion && count($uuids)) {
                $json['CfdiRelacionados'] = [
                    'TipoRelacion' => $tipoRelacion,
                    'CfdiRelacionado' => $uuids,
                ];
            }
        }

        return $json;
    }
}
