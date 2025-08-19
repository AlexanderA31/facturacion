<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Factura</title>
    <style>
        body { font-family: sans-serif; }
        .container { width: 100%; margin: 0 auto; }
        .header, .footer { text-align: center; }
        .content { margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            @if(isset($logo_path))
                <img src="{{ public_path('storage/' . $logo_path) }}" alt="Logo" style="max-width: 150px; max-height: 100px;"/>
            @endif
            <h1>Factura</h1>
        </div>
        <div class="content">
            <h2>Información del Cliente</h2>
            <p><span class="bold">Razón Social:</span> {{ $infoTributaria->razonSocial ?? 'N/A' }}</p>
            <p><span class="bold">RUC/CI:</span> {{ $infoFactura->identificacionComprador ?? 'N/A' }}</p>
            <p><span class="bold">Fecha Emisión:</span> {{ $infoFactura->fechaEmision ?? 'N/A' }}</p>
            <p><span class="bold">Clave de Acceso:</span> {{ $infoTributaria->claveAcceso ?? 'N/A' }}</p>

            <h2>Detalles de la Factura</h2>
            <table>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Descripción</th>
                        <th>Cantidad</th>
                        <th>Precio Unit.</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($detalles as $detalle)
                    <tr>
                        <td>{{ $detalle->codigoPrincipal ?? 'N/A' }}</td>
                        <td>{{ $detalle->descripcion ?? 'N/A' }}</td>
                        <td>{{ $detalle->cantidad ?? 'N/A' }}</td>
                        <td class="text-right">{{ number_format((float)($detalle->precioUnitario ?? 0), 2) }}</td>
                        <td class="text-right">{{ number_format((float)($detalle->precioTotalSinImpuesto ?? 0), 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <h2>Totales</h2>
            <table style="width: 40%; margin-left: 60%;">
                <tr>
                    <td class="bold">Subtotal:</td>
                    <td class="text-right">{{ number_format((float)($infoFactura->totalSinImpuestos ?? 0), 2) }}</td>
                </tr>
                @foreach($infoFactura->totalConImpuestos->totalImpuesto as $impuesto)
                <tr>
                    <td class="bold">IVA ({{ number_format((float)($impuesto->tarifa ?? 0), 2) }}%):</td>
                    <td class="text-right">{{ number_format((float)($impuesto->valor ?? 0), 2) }}</td>
                </tr>
                @endforeach
                <tr>
                    <td class="bold">Total:</td>
                    <td class="text-right">{{ number_format((float)($infoFactura->importeTotal ?? 0), 2) }}</td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
