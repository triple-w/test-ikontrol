<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Vista previa</title>
<style>
body{font-family:ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Ubuntu; padding:24px; color:#111827;}
table{width:100%; border-collapse:collapse;}
th,td{border:1px solid #e5e7eb; padding:8px; font-size:12px;}
th{background:#f9fafb; text-align:left;}
.small{font-size:12px;color:#6b7280}
.right{text-align:right}
</style>
</head>
<body>
<h2>Factura ({{ data_get($encabezado,'tipo') }})</h2>
<p class="small">Serie {{ data_get($encabezado,'serie') }} · Folio {{ data_get($encabezado,'folio') }} · Fecha {{ data_get($encabezado,'fecha') }}</p>

<h3>Receptor</h3>
<p>
    <strong>{{ data_get($cliente,'razon_social') }}</strong><br>
    RFC: {{ data_get($cliente,'rfc') }}<br>
    {{ data_get($cliente,'calle') }} {{ data_get($cliente,'no_ext') }}
    {{ data_get($cliente,'no_int') ? ' Int. '.data_get($cliente,'no_int') : '' }},
    Col. {{ data_get($cliente,'colonia') }}, {{ data_get($cliente,'localidad') }},
    {{ data_get($cliente,'estado') }}, CP {{ data_get($cliente,'codigo_postal') }}, {{ data_get($cliente,'pais') }}<br>
    Email: {{ data_get($cliente,'email') }}
</p>


<h3>Conceptos</h3>
<table>
    <thead>
        <tr>
            <th>Descripción</th><th>Clave PS</th><th>Cant</th><th>Precio</th><th class="right">Importe</th>
        </tr>
    </thead>
    <tbody>
        @foreach($conceptos as $c)
        <tr>
            <td>{{ $c['descripcion'] }}</td>
            <td>{{ $c['clave_prod_serv_id'] }}</td>
            <td>{{ $c['cantidad'] }}</td>
            <td class="right">${{ number_format($c['precio'],2) }}</td>
            <td class="right">${{ number_format($c['total'],2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<h3>Totales</h3>
<p>
    Subtotal: ${{ number_format(data_get($totales,'subtotal',0),2) }}<br>
    Trasladados: ${{ number_format(data_get($totales,'trasladados',0),2) }}<br>
    Retenidos: ${{ number_format(data_get($totales,'retenidos',0),2) }}<br>
    <strong>Total: ${{ number_format(data_get($totales,'total',0),2) }}</strong>
</p>
</body>
</html>
