<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Comprobante Electrónico</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            padding-bottom: 20px;
        }
        .header img {
            max-width: 150px;
        }
        .content {
            padding: 20px 0;
        }
        .content h1 {
            color: #333333;
            font-size: 22px;
        }
        .content p {
            color: #555555;
            line-height: 1.6;
        }
        .invoice-details {
            margin: 20px 0;
            padding: 15px;
            background-color: #f9f9f9;
            border-left: 4px solid #007bff;
        }
        .invoice-details p {
            margin: 5px 0;
        }
        .button-container {
            text-align: center;
            padding-top: 20px;
        }
        .button {
            background-color: #007bff;
            color: #ffffff;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
            display: inline-block;
        }
        .footer {
            text-align: center;
            padding-top: 20px;
            font-size: 12px;
            color: #999999;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            @if(isset($logoUrl))
                <img src="{{ $logoUrl }}" alt="Logo de la Empresa">
            @endif
        </div>
        <div class="content">
            <h1>Nuevo Comprobante Electrónico</h1>
            <p>Estimado cliente,</p>
            <p>Se ha generado un nuevo comprobante electrónico para usted. A continuación encontrará los detalles:</p>

            <div class="invoice-details">
                <p><strong>Clave de Acceso:</strong> {{ $claveAcceso }}</p>
                <p><strong>Importe Total:</strong> ${{ number_format($total, 2) }}</p>
            </div>

            <p>Los archivos XML y PDF de su comprobante se encuentran adjuntos a este correo.</p>

            <div class="button-container">
                <a href="{{ $pdfUrl }}" class="button" target="_blank">Ver Comprobante PDF</a>
            </div>
        </div>
        <div class="footer">
            <p>Este es un correo electrónico generado automáticamente. Por favor, no responda a este mensaje.</p>
        </div>
    </div>
</body>
</html>
