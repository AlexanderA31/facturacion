<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Factura</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; }
        .container { width: 100%; margin: 0 auto; }
        .header-container { display: table; width: 100%; border: 1px solid #ddd; }
        .emitter-container { display: table-cell; width: 50%; vertical-align: top; padding: 10px; }
        .invoice-info-container { display: table-cell; width: 50%; vertical-align: top; border-left: 1px solid #ddd; padding: 10px;}
        .client-info-container { margin-top: 10px; border: 1px solid #ddd; padding: 10px; }
        .content { margin-top: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 5px; }
        th { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .bold { font-weight: bold; }
        .totals-container { display: table; width: 100%; margin-top: 10px; }
        .additional-info-container { display: table-cell; width: 60%; vertical-align: top; border: 1px solid #ddd; padding: 10px; }
        .totals-table-container { display: table-cell; width: 40%; vertical-align: top; padding-left: 10px; }
        .totals-table { width: 100%; }
        .totals-table td { border: 1px solid #ddd; padding: 5px; }
        p { margin: 2px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-container">
            <div class="emitter-container">
                @if(isset($logo_path))
                    <img src="{{ public_path('storage/' . $logo_path) }}" alt="Logo" style="max-width: 200px; max-height: 100px; margin-bottom: 10px;"/>
                @endif
                <p><span class="bold">Emisor:</span> {{ $infoTributaria->razonSocial }}</p>
                <p><span class="bold">RUC:</span> {{ $infoTributaria->ruc }}</p>
                <p><span class="bold">Matriz:</span> {{ $infoTributaria->dirMatriz }}</p>
                <p><span class="bold">Correo:</span> {{ $user->email }}</p>
                {{-- No hay campo de telefono en la tabla de usuarios --}}
                <p><span class="bold">Obligado a llevar contabilidad:</span> {{ $infoFactura->obligadoContabilidad }}</p>
                @if(isset($infoTributaria->regimenMicroempresas))
                <p><span class="bold">CONTRIBUYENTE RÉGIMEN RIMPE</span></p>
                @endif
            </div>
            <div class="invoice-info-container">
                <p><span class="bold">FACTURA No.</span> {{ $infoTributaria->estab }}-{{ $infoTributaria->ptoEmi }}-{{ $infoTributaria->secuencial }}</p>
                <p><span class="bold">Número de Autorización:</span></p>
                <p>{{ $numeroAutorizacion }}</p>
                <p><span class="bold">Fecha y hora de Autorización:</span> {{ $fechaAutorizacion }}</p>
                <p><span class="bold">Ambiente:</span> {{ $infoTributaria->ambiente == '1' ? 'PRUEBAS' : 'PRODUCCIÓN' }}</p>
                <p><span class="bold">Emisión:</span> {{ $infoTributaria->tipoEmision == '1' ? 'NORMAL' : 'CONTINGENCIA' }}</p>
                <p><span class="bold">Clave de Acceso:</span></p>
                <p style="font-size: 9px; word-wrap: break-word;">{{ $infoTributaria->claveAcceso }}</p>
                <div style="text-align: center; margin-top: 10px;">
                    <img src="{{ $barcode_path }}" alt="barcode" />
                </div>
            </div>
        </div>

        @php
            $clientEmail = '';
            $clientTelefono = '';
            $infoAdicionalCampos = [];
            if (isset($infoAdicional) && $infoAdicional->campoAdicional) {
                foreach ($infoAdicional->campoAdicional as $campo) {
                    $nombre = strtolower((string)$campo['nombre']);
                    $valor = (string)$campo;
                    if ($nombre === 'email' || $nombre === 'correo') {
                        $clientEmail = $valor;
                    } elseif ($nombre === 'telefono' || $nombre === 'teléfono') {
                        $clientTelefono = $valor;
                    } else {
                        $infoAdicionalCampos[] = ['nombre' => (string)$campo['nombre'], 'valor' => $valor];
                    }
                }
            }
        @endphp

        <div class="client-info-container">
            <p><span class="bold">Razón Social:</span> {{ $infoFactura->razonSocialComprador }}</p>
            <p><span class="bold">RUC/CI:</span> {{ $infoFactura->identificacionComprador }}</p>
            <p><span class="bold">Dirección:</span> {{ $infoFactura->direccionComprador ?? 'N/A' }}</p>
            <p><span class="bold">Teléfono:</span> {{ $clientTelefono }}</p>
            <p><span class="bold">Fecha Emisión:</span> {{ $infoFactura->fechaEmision }}</p>
            <p><span class="bold">Correo:</span> {{ $clientEmail }}</p>
        </div>

        <div class="content">
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
        </div>

        <div class="totals-container">
            <div class="additional-info-container">
                <p class="bold">Información Adicional</p>
                @if(count($infoAdicionalCampos) > 0)
                    @foreach($infoAdicionalCampos as $campo)
                        <p><span class="bold">{{ $campo['nombre'] }}:</span> {{ $campo['valor'] }}</p>
                    @endforeach
                @else
                    <p>No hay información adicional.</p>
                @endif

                @php
                    $formasPago = [
                        '01' => 'Sin utilización del sistema financiero',
                        '15' => 'Compensación de deudas',
                        '16' => 'Tarjeta de débito',
                        '17' => 'Dinero electrónico',
                        '18' => 'Tarjeta prepago',
                        '19' => 'Tarjeta de crédito',
                        '20' => 'Otros con utilización del sistema financiero',
                        '21' => 'Endoso de títulos',
                    ];
                @endphp

                @if(isset($infoFactura->pagos))
                    <p class="bold" style="margin-top: 10px;">Formas de Pago</p>
                    <table style="border: none;">
                        <thead style="display: none;">
                            <tr>
                                <th>Descripción</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($infoFactura->pagos->pago as $pago)
                                <tr>
                                    <td style="border: none;">{{ $formasPago[(string)$pago->formaPago] ?? (string)$pago->formaPago }}</td>
                                    <td class="text-right" style="border: none;">{{ number_format((float)$pago->total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
            <div class="totals-table-container">
                @php
                    // Initialize all possible totals to 0
                    $subtotal15 = 0;
                    $iva15 = 0;
                    $subtotal5 = 0;
                    $iva5 = 0;
                    $subtotal0 = 0;
                    $subtotalNoObjeto = 0;
                    $ice = 0;

                    // Process the taxes from the invoice data
                    if (isset($infoFactura->totalConImpuestos)) {
                        foreach($infoFactura->totalConImpuestos->totalImpuesto as $impuesto) {
                            if ($impuesto->codigo == '2') { // IVA
                                switch ($impuesto->codigoPorcentaje) {
                                    case '4': // 15%
                                        $subtotal15 = (float)$impuesto->baseImponible;
                                        $iva15 = (float)$impuesto->valor;
                                        break;
                                    case '5': // 5% (Guess)
                                        $subtotal5 = (float)$impuesto->baseImponible;
                                        $iva5 = (float)$impuesto->valor;
                                        break;
                                    case '0': // 0%
                                        $subtotal0 = (float)$impuesto->baseImponible;
                                        break;
                                    case '6': // No Objeto de IVA
                                        $subtotalNoObjeto = (float)$impuesto->baseImponible;
                                        break;
                                }
                            } elseif ($impuesto->codigo == '3') { // ICE
                                $ice += (float)$impuesto->valor;
                            }
                        }
                    }
                @endphp
                <table class="totals-table">
                    <tr>
                        <td class="bold text-left">Subtotal Sin Impuestos:</td>
                        <td class="text-right">${{ number_format((float)$infoFactura->totalSinImpuestos, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="bold text-left">Subtotal 15%:</td>
                        <td class="text-right">${{ number_format($subtotal15, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="bold text-left">Subtotal 5%:</td>
                        <td class="text-right">${{ number_format($subtotal5, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="bold text-left">Subtotal 0%:</td>
                        <td class="text-right">${{ number_format($subtotal0, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="bold text-left">Subtotal No Objeto IVA:</td>
                        <td class="text-right">${{ number_format($subtotalNoObjeto, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="bold text-left">Descuentos:</td>
                        <td class="text-right">${{ number_format((float)$infoFactura->totalDescuento, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="bold text-left">ICE:</td>
                        <td class="text-right">${{ number_format($ice, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="bold text-left">IVA 15%:</td>
                        <td class="text-right">${{ number_format($iva15, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="bold text-left">IVA 5%:</td>
                        <td class="text-right">${{ number_format($iva5, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="bold text-left">Servicio %:</td>
                        <td class="text-right">${{ number_format((float)$infoFactura->propina, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="bold text-left">Valor Total:</td>
                        <td class="text-right">${{ number_format((float)$infoFactura->importeTotal, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
