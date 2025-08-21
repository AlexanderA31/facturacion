<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Comprobante Electrónico</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Lato:wght@400;700&display=swap');
        body {
            font-family: 'Lato', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            color: #333333;
        }
        .email-container {
            max-width: 680px;
            margin: 20px auto;
            background-color: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
        }
        .email-header {
            background-color: #0d6efd;
            color: #ffffff;
            padding: 40px;
            text-align: center;
        }
        .email-header img {
            max-width: 150px;
            margin-bottom: 20px;
        }
        .email-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        .email-body {
            padding: 40px;
        }
        .email-body p {
            font-size: 16px;
            line-height: 1.7;
            margin: 0 0 16px;
        }
        .invoice-details {
            margin: 30px 0;
            padding: 20px;
            background-color: #f8f9fa;
            border-left: 5px solid #0d6efd;
        }
        .invoice-details p {
            margin: 8px 0;
            font-size: 16px;
        }
        .invoice-details strong {
            color: #0d6efd;
        }
        .button-container {
            text-align: center;
            margin-top: 30px;
        }
        .button {
            background-color: #198754;
            color: #ffffff;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 50px;
            font-size: 18px;
            font-weight: 700;
            display: inline-block;
            transition: background-color 0.3s ease;
        }
        .button:hover {
            background-color: #157347;
        }
        .email-footer {
            text-align: center;
            padding: 20px;
            font-size: 14px;
            color: #6c757d;
            background-color: #f8f9fa;
            border-top: 1px solid #dee2e6;
        }
        .email-footer p {
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            @if(isset($logoUrl))
                <img src="{{ $logoUrl }}" alt="Logo de la Empresa">
            @endif
            <h1>Nuevo Comprobante Electrónico</h1>
        </div>
        <div class="email-body">
            <p>Estimado cliente,</p>
            <p>Hemos generado un nuevo comprobante electrónico para usted. Puede encontrar un resumen de los detalles a continuación y los archivos adjuntos en este correo.</p>

            <div class="invoice-details">
                <p><strong>Clave de Acceso:</strong><br>{{ $claveAcceso }}</p>
                <p><strong>Importe Total:</strong><br>${{ number_format($total, 2) }}</p>
            </div>

            <p>Para su conveniencia, también puede descargar el comprobante en formato PDF directamente desde el siguiente enlace:</p>

            <div class="button-container">
                <a href="{{ $pdfUrl }}" class="button" download>Descargar PDF</a>
            </div>
        </div>
        <div class="email-footer">
            <p>Este es un correo electrónico generado automáticamente. Por favor, no responda a este mensaje.</p>
        </div>
    </div>
</body>
</html>
