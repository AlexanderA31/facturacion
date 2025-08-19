<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Factura</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .container { width: 100%; margin: 0 auto; }
        .header-container { display: table; width: 100%; }
        .logo-container { display: table-cell; width: 50%; vertical-align: top; }
        .invoice-info-container { display: table-cell; width: 50%; vertical-align: top; text-align: right; }
        .invoice-info { border: 1px solid #ddd; padding: 10px; }
        .content { margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .bold { font-weight: bold; }
        .section-title { background-color: #f2f2f2; padding: 5px; font-weight: bold; margin-top: 20px; }
        .info-table { margin-bottom: 20px; }
        .info-table td { border: none; padding: 2px; }
        .totals-table { width: 40%; margin-left: 60%; }
        .totals-table td { border: 1px solid #ddd; padding: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-container">
            <div class="logo-container">
                @if(isset($logo_path))
                    <img src="{{ public_path('storage/' . $logo_path) }}" alt="Logo" style="max-width: 250px; max-height: 150px;"/>
                @endif
                <div class="section-title">DATOS EMISOR</div>
                <p><span class="bold">{{ $infoTributaria->razonSocial }}</span></p>
                <p>Dir Matriz: {{ $infoTributaria->dirMatriz }}</p>
            </div>
            <div class="invoice-info-container">
                <div class="invoice-info">
                    <p><span class="bold">R.U.C.:</span> {{ $infoTributaria->ruc }}</p>
                    <p><span class="bold">FACTURA</span></p>
                    <p><span class="bold">No.</span> {{ $infoTributaria->estab }}-{{ $infoTributaria->ptoEmi }}-{{ $infoTributaria->secuencial }}</p>
                    <p><span class="bold">NÚMERO DE AUTORIZACIÓN</span></p>
                    <p>{{ $infoTributaria->claveAcceso }}</p>
                    <p><span class="bold">AMBIENTE:</span> {{ $infoTributaria->ambiente == '1' ? 'PRUEBAS' : 'PRODUCCIÓN' }}</p>
                    <p><span class="bold">EMISIÓN:</span> {{ $infoTributaria->tipoEmision == '1' ? 'NORMAL' : 'CONTINGENCIA' }}</p>
                    <p><span class="bold">CLAVE DE ACCESO</span></p>
                    <p style="font-size: 10px; word-wrap: break-word;">{{ $infoTributaria->claveAcceso }}</p>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="section-title">DATOS DEL CLIENTE</div>
            <table class="info-table">
                <tr>
                    <td><span class="bold">Razón Social:</span></td>
                    <td>{{ $infoFactura->razonSocialComprador }}</td>
                </tr>
                <tr>
                    <td><span class="bold">Identificación:</span></td>
                    <td>{{ $infoFactura->identificacionComprador }}</td>
                </tr>
                <tr>
                    <td><span class="bold">Fecha Emisión:</span></td>
                    <td>{{ $infoFactura->fechaEmision }}</td>
                </tr>
                <tr>
                    <td><span class="bold">Dirección:</span></td>
                    <td>{{ $infoFactura->direccionComprador ?? 'N/A' }}</td>
                </tr>
            </table>

            <div class="section-title">DETALLES DE LA FACTURA</div>
            <table>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Descripción</th>
                        <th>Cantidad</th>
                        <th>Precio Unit.</th>
                        <th>Descuento</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($detalles as $detalle)
                    <tr>
                        <td>{{ $detalle->codigoPrincipal }}</td>
                        <td>{{ $detalle->descripcion }}</td>
                        <td class="text-right">{{ $detalle->cantidad }}</td>
                        <td class="text-right">{{ number_format((float)$detalle->precioUnitario, 2) }}</td>
                        <td class="text-right">{{ number_format((float)$detalle->descuento, 2) }}</td>
                        <td class="text-right">{{ number_format((float)$detalle->precioTotalSinImpuesto, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <table style="width: 100%; margin-top: 20px;">
                <tr>
                    <td style="width: 60%; vertical-align: top;">
                        <div class="section-title">Información Adicional</div>
                        @if(isset($infoAdicional) && count($infoAdicional->campoAdicional) > 0)
                            @foreach($infoAdicional->campoAdicional as $info)
                                <p><span class="bold">{{ $info['nombre'] }}:</span> {{ $info }}</p>
                            @endforeach
                        @else
                            <p>No hay información adicional.</p>
                        @endif
                    </td>
                    <td style="width: 40%; vertical-align: top;">
                        <table class="totals-table">
                            <tr>
                                <td class="bold text-left">Subtotal Sin Impuestos:</td>
                                <td class="text-right">{{ number_format((float)$infoFactura->totalSinImpuestos, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="bold text-left">Total Descuento:</td>
                                <td class="text-right">{{ number_format((float)$infoFactura->totalDescuento, 2) }}</td>
                            </tr>
                            @foreach($infoFactura->totalConImpuestos->totalImpuesto as $impuesto)
                                @php
                                    $tarifa = (float)$impuesto->tarifa;
                                    $baseImponible = (float)$impuesto->baseImponible;
                                    $valor = (float)$impuesto->valor;
                                    $codigo = (string)$impuesto->codigo;
                                    $codigoPorcentaje = (string)$impuesto->codigoPorcentaje;
                                    $label = 'IVA';
                                    if ($codigo == '2') { // IVA
                                        if ($codigoPorcentaje == '0') $label = 'IVA 0%';
                                        elseif ($codigoPorcentaje == '2') $label = 'IVA 12%';
                                        elseif ($codigoPorcentaje == '3') $label = 'IVA 14%';
                                        elseif ($codigoPorcentaje == '4') $label = 'IVA 15%';
                                        else $label = 'IVA (' . $tarifa . '%)';
                                    } elseif ($codigo == '3') { // ICE
                                        $label = 'ICE';
                                    } elseif ($codigo == '5') { // IRBPNR
                                        $label = 'IRBPNR';
                                    }
                                @endphp
                                <tr>
                                    <td class="bold text-left">Subtotal {{ $label }}:</td>
                                    <td class="text-right">{{ number_format($baseImponible, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="bold text-left">{{ $label }} Valor:</td>
                                    <td class="text-right">{{ number_format($valor, 2) }}</td>
                                </tr>
                            @endforeach
                            <tr>
                                <td class="bold text-left">Propina:</td>
                                <td class="text-right">{{ number_format((float)$infoFactura->propina, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="bold text-left">Importe Total:</td>
                                <td class="text-right">{{ number_format((float)$infoFactura->importeTotal, 2) }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
