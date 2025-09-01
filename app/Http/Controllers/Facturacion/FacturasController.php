<?php

namespace App\Http\Controllers\Facturacion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class FacturasController extends Controller
{
    /** Pantalla única para capturar todo */
    public function create(Request $request)
    {
        $rfcId = (int) session('rfc_usuario_id');  // RFC activo

        // Emisor (RFC activo)
        $emisor = DB::table('rfc_usuarios')->where('id', $rfcId)->first();

        // Catálogos mínimos (si ya tienes tablas SAT, cámbialo por consultas)
        $clientes = DB::table('clientes')
            ->when($rfcId, fn($q) => $q->where('rfc_usuario_id', $rfcId))
            ->orderBy('razon_social')
            ->get(['id','rfc','razon_social','codigo_postal','email','regimen_fiscal']);

        $productos = DB::table('productos')
            ->when($rfcId, fn($q) => $q->where('rfc_usuario_id', $rfcId))
            ->orderBy('descripcion')
            ->limit(200)
            ->get(['id','descripcion','clave_prod_serv','clave_unidad','unidad','precio','objeto_imp']);

        $usosCfdi   = ['G01','G02','G03','S01','CP01','D01','D02','D03','D04','D05','D06','D07','D08','D09','D10','P01'];
        $formasPago = ['01'=>'Efectivo','02'=>'Cheque','03'=>'Transferencia','04'=>'Tarjeta crédito','28'=>'Tarjeta débito','99'=>'Por definir'];
        $metodos    = ['PUE'=>'PUE','PPD'=>'PPD'];
        $monedas    = ['MXN'=>'MXN','USD'=>'USD','EUR'=>'EUR'];
        $tiposComprobante = ['I'=>'Ingreso','E'=>'Egreso','P'=>'Pago','T'=>'Traslado'];
        $tiposRelacion   = ['01'=>'Nota de crédito','02'=>'Nota de débito','03'=>'Devolución','04'=>'Sustitución','07'=>'Aplicación de anticipo'];

        return view('facturacion.facturas.crear', compact(
            'emisor','clientes','productos',
            'usosCfdi','formasPago','metodos','monedas',
            'tiposComprobante','tiposRelacion'
        ));
    }

    /** Recibe TODO el formulario; decide: guardar borrador o timbrar */
    public function store(Request $request)
    {
        $rfcId = (int) session('rfc_usuario_id');

        // Validación base (suma los campos que uses)
        Validator::make($request->all(), [
            'tipoComprobante' => 'required|in:I,E,P,T',
            'cliente_id'      => 'required|integer|exists:clientes,id',
            'usoCFDI'         => 'required|string|max:5',
            'moneda'          => 'required|string|max:3',
            'formaPago'       => 'nullable|string|max:3',
            'metodoPago'      => 'nullable|string|max:3',
            'lugarExpedicion' => 'required|string|max:10',
            'fecha'           => 'required|date',

            'conceptos'       => 'required|array|min:1',
            'conceptos.*.descripcion'     => 'required|string|max:1000',
            'conceptos.*.clave_prod_serv' => 'required|string|max:8',
            'conceptos.*.clave_unidad'    => 'required|string|max:5',
            'conceptos.*.unidad'          => 'nullable|string|max:15',
            'conceptos.*.cantidad'        => 'required|numeric|min:0.001',
            'conceptos.*.precio'          => 'required|numeric|min:0',
            'conceptos.*.descuento'       => 'nullable|numeric|min:0',
            'conceptos.*.iva'             => 'nullable|numeric|min:0', // porcentaje (ej. 16)
        ])->validate();

        $cliente = DB::table('clientes')->where('id', $request->cliente_id)->first();
        $emisor  = DB::table('rfc_usuarios')->where('id', $rfcId)->first();

        // Totales rápidos (frontend ya los calcula; backend recalcula)
        $sub = 0; $desc = 0; $iva = 0;
        foreach ($request->conceptos as $c) {
            $importe = (float)$c['cantidad'] * (float)$c['precio'];
            $d      = (float)($c['descuento'] ?? 0);
            $base   = max($importe - $d, 0);
            $sub   += $importe;
            $desc  += $d;
            $iva   += $base * (float)($c['iva'] ?? 0) / 100;
        }
        $total = $sub - $desc + $iva;

        // ====== MODO BORRADOR o TIMBRADO ======
        $accion = $request->input('accion'); // "borrador" | "timbrar"

        // Guarda registro local (mínimo indispensable)
        $facturaId = DB::table('facturas')->insertGetId([
            'users_id'        => auth()->id(),
            'rfc_usuario_id'  => $rfcId,
            'rfc'             => $emisor->rfc ?? '',
            'razon_social'    => $emisor->razon_social ?? '',
            'codigo_postal'   => $emisor->codigo_postal ?? '',
            'estatus'         => $accion === 'timbrar' ? 'generada' : 'borrador',
            'fecha'           => Carbon::parse($request->fecha)->toDateString(),
            'fecha_factura'   => Carbon::parse($request->fecha)->toDateTimeString(),
            'tipo_comprobante'=> $request->tipoComprobante,
            'nombre_comprobante' => 'CFDI 4.0',
            'comentarios_pdf' => $request->comentarios_pdf ?? null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        // Detalles (si tienes tabla de detalles, insértalos aquí)
        // DB::table('facturas_detalles')->insert([...]);

        if ($accion === 'borrador') {
            return redirect()->route('facturas.crear')
                ->with('ok', "Borrador guardado (#{$facturaId}).");
        }

        // ===== Timbrado (Dummy hasta conectar PAC) =====
        // Construimos un XML mínimo (sin Timbre) sólo para que el dashboard pueda sumar totales.
        $xml = $this->construirXmlMinimo40($request, $emisor, $cliente, $sub, $desc, $iva, $total);

        // Simula UUID y PDF base64 (mientras integramos el PAC real)
        $uuid = strtoupper(\Str::uuid()->toString());
        $pdfBase64 = base64_encode('%PDF-1.4 Dummy');

        DB::table('facturas')->where('id', $facturaId)->update([
            'xml'   => $xml,
            'uuid'  => $uuid,
            'pdf'   => $pdfBase64,
            'updated_at' => now(),
        ]);

        // TODO: cuando tengamos el PAC, aquí llamamos al servicio real
        //       y guardamos el XML timbrado + acuse.

        return redirect()->route('facturas.crear')
            ->with('ok', "Factura timbrada (simulada) UUID {$uuid}.");
    }

    /** XML mínimo CFDI 4.0 (para pruebas y totales en dashboard) */
    private function construirXmlMinimo40(Request $r, $emisor, $cliente, $sub, $desc, $iva, $total): string
    {
        $fecha = Carbon::parse($r->fecha)->format('Y-m-d\TH:i:s');
        $serie = trim($r->serie ?? '');
        $folio = trim($r->folio ?? '');
        $lugar = $r->lugarExpedicion;

        $attrSerie = $serie ? ' Serie="'.$this->e($serie).'"' : '';
        $attrFolio = $folio ? ' Folio="'.$this->e($folio).'"' : '';

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<cfdi:Comprobante xmlns:cfdi="http://www.sat.gob.mx/cfd/4" Version="4.0"';
        $xml .= $attrSerie.$attrFolio;
        $xml .= ' Fecha="'.$fecha.'" Moneda="'.$this->e($r->moneda).'" TipoDeComprobante="'.$this->e($r->tipoComprobante).'"';
        if ($r->metodoPago) $xml .= ' MetodoPago="'.$this->e($r->metodoPago).'"';
        if ($r->formaPago)  $xml .= ' FormaPago="'.$this->e($r->formaPago).'"';
        $xml .= ' SubTotal="'.number_format($sub - $desc, 2, '.', '').'"';
        $xml .= ' Total="'.number_format($total, 2, '.', '').'"';
        $xml .= ' LugarExpedicion="'.$this->e($lugar).'">';

        $xml .= '<cfdi:Emisor Rfc="'.$this->e($emisor->rfc).'" Nombre="'.$this->e($emisor->razon_social).'" RegimenFiscal="'.($emisor->regimen_fiscal ?? '601').'" />';
        $xml .= '<cfdi:Receptor Rfc="'.$this->e($cliente->rfc).'" Nombre="'.$this->e($cliente->razon_social).'" DomicilioFiscalReceptor="'.$this->e($cliente->codigo_postal).'" UsoCFDI="'.$this->e($r->usoCFDI).'" RegimenFiscalReceptor="'.($cliente->regimen_fiscal ?? '601').'" />';

        $xml .= '<cfdi:Conceptos>';
        foreach ($r->conceptos as $c) {
            $cant = (float)$c['cantidad'];
            $pre  = (float)$c['precio'];
            $des  = (float)($c['descuento'] ?? 0);
            $imp  = $cant * $pre;
            $base = max($imp - $des, 0);
            $xml .= '<cfdi:Concepto Cantidad="'.number_format($cant, 3, '.', '').'" ClaveProdServ="'.$this->e($c['clave_prod_serv']).'" ClaveUnidad="'.$this->e($c['clave_unidad']).'"';
            if (!empty($c['unidad'])) $xml .= ' Unidad="'.$this->e($c['unidad']).'"';
            $xml .= ' Descripcion="'.$this->e($c['descripcion']).'" ValorUnitario="'.number_format($pre, 2, '.', '').'" Importe="'.number_format($imp, 2, '.', '').'" Descuento="'.number_format($des, 2, '.', '').'">';

            $ivaPorc = (float)($c['iva'] ?? 0);
            if ($ivaPorc > 0) {
                $xml .= '<cfdi:Impuestos><cfdi:Traslados>';
                $xml .= '<cfdi:Traslado Base="'.number_format($base, 2, '.', '').'" Impuesto="002" TipoFactor="Tasa" TasaOCuota="'.number_format($ivaPorc/100, 6, '.', '').'" Importe="'.number_format($base*$ivaPorc/100, 2, '.', '').'"/>';
                $xml .= '</cfdi:Traslados></cfdi:Impuestos>';
            }

            $xml .= '</cfdi:Concepto>';
        }
        $xml .= '</cfdi:Conceptos>';

        if ($iva > 0) {
            $xml .= '<cfdi:Impuestos TotalImpuestosTrasladados="'.number_format($iva, 2, '.', '').'"><cfdi:Traslados>';
            $xml .= '<cfdi:Traslado Base="'.number_format($sub - $desc, 2, '.', '').'" Impuesto="002" TipoFactor="Tasa" TasaOCuota="'.number_format($this->tasapromedio($r->conceptos), 6, '.', '').'" Importe="'.number_format($iva, 2, '.', '').'"/></cfdi:Traslados></cfdi:Impuestos>';
        }

        // Relaciones (opcional)
        if ($r->filled('tipoRelacion') && $r->filled('uuidsRelacionados')) {
            $xml .= '<cfdi:CfdiRelacionados TipoRelacion="'.$this->e($r->tipoRelacion).'">';
            foreach (preg_split('/\s+/', trim($r->uuidsRelacionados)) as $u) {
                if ($u) $xml .= '<cfdi:CfdiRelacionado UUID="'.$this->e($u).'" />';
            }
            $xml .= '</cfdi:CfdiRelacionados>';
        }

        $xml .= '</cfdi:Comprobante>';
        return $xml;
    }

    private function tasapromedio(array $conceptos): float
    {
        $bases = 0; $ivas = 0;
        foreach ($conceptos as $c) {
            $imp  = (float)$c['cantidad'] * (float)$c['precio'];
            $des  = (float)($c['descuento'] ?? 0);
            $base = max($imp - $des, 0);
            $porc = (float)($c['iva'] ?? 0) / 100;
            $bases += $base;
            $ivas  += $base * $porc;
        }
        if ($bases <= 0) return 0.0;
        return $ivas / $bases;
    }

    private function e(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
