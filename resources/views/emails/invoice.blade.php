<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Comprobante Electrónico</title>
</head>
<body style="font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f4;">
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="600" style="border-collapse: collapse; margin: 20px auto; background-color: #ffffff; border: 1px solid #dddddd; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <!-- Header con Logo -->
        <tr>
            <td align="center" style="padding: 20px 0 20px 0; border-bottom: 1px solid #eeeeee;">
                @if(!empty($logo_path))
                    <img src="{{ $logo_path }}" alt="Logo de la Empresa" style="display: block; max-width: 200px; max-height: 80px;">
                @else
                    <h1 style="margin: 0;">{{ $user_name }}</h1>
                @endif
            </td>
        </tr>
        <!-- Contenido Principal -->
        <tr>
            <td style="padding: 30px 40px;">
                <h2 style="color: #333333; margin-top: 0;">Nuevo Comprobante Electrónico</h2>
                <p style="color: #555555; font-size: 16px; line-height: 1.5;">
                    Estimado(a) {{ $client_name }},
                </p>
                <p style="color: #555555; font-size: 16px; line-height: 1.5;">
                    Se ha generado un nuevo comprobante electrónico para usted. A continuación encontrará los detalles de su factura.
                </p>

                <!-- Detalles de la Factura -->
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse; margin-top: 20px; border: 1px solid #dddddd;">
                    <tr>
                        <td style="padding: 12px; background-color: #f9f9f9; font-weight: bold; color: #333;">Número de Factura:</td>
                        <td style="padding: 12px; color: #555;">{{ $invoice_number }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px; background-color: #f9f9f9; font-weight: bold; color: #333;">Fecha de Emisión:</td>
                        <td style="padding: 12px; color: #555;">{{ $invoice_date }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px; background-color: #f9f9f9; font-weight: bold; color: #333; font-size: 18px;">Monto Total:</td>
                        <td style="padding: 12px; color: #555; font-size: 18px; font-weight: bold;">${{ number_format($invoice_total, 2) }}</td>
                    </tr>
                </table>

                <!-- Botón de Previsualización -->
                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td align="center" style="padding: 30px 0;">
                            <a href="{{ $preview_url }}" style="background-color: #007bff; color: #ffffff; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 16px; display: inline-block;">
                                Previsualizar Factura (PDF)
                            </a>
                        </td>
                    </tr>
                </table>

                <p style="color: #555555; font-size: 16px; line-height: 1.5;">
                    También encontrará los archivos XML y PDF de su factura adjuntos a este correo.
                </p>
                <p style="color: #555555; font-size: 16px; line-height: 1.5;">
                    Gracias por su preferencia.
                </p>
            </td>
        </tr>
        <!-- Footer -->
        <tr>
            <td align="center" style="padding: 20px; background-color: #f2f2f2; border-top: 1px solid #dddddd;">
                <p style="margin: 0; color: #888888; font-size: 12px;">
                    Este es un correo electrónico generado automáticamente. Por favor, no responda a este mensaje.
                </p>
                <p style="margin: 5px 0 0 0; color: #888888; font-size: 12px;">
                    &copy; {{ date('Y') }} {{ $user_name }}. Todos los derechos reservados.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
