<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SoapClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use dealfonso\sapp\PDF\Signature;

class SriProxyController extends Controller
{
    public function getComprobantePdf($claveAcceso)
    {
        try {
            // 1. Get the XML content
            $xmlResponse = $this->getComprobante($claveAcceso, true);
            if ($xmlResponse->getStatusCode() !== 200) {
                return $xmlResponse; // Return the error response from getComprobante
            }
            $xmlContent = $xmlResponse->getOriginalContent();
            $xml = simplexml_load_string($xmlContent);

            if ($xml === false) {
                throw new \Exception('No se pudo parsear el XML del comprobante.');
            }

            // 2. Prepare data for the Blade view
            $data = [
                'infoTributaria' => $xml->infoTributaria,
                'infoFactura' => $xml->infoFactura,
                'detalles' => $xml->detalles->detalle,
            ];

            // 3. Generate PDF from Blade view
            $pdf = Pdf::loadView('pdf.invoice', $data);
            $unsignedPdfContent = $pdf->output();

            // 4. Sign the PDF
            $user = auth()->user();
            if (!$user->signature_path || !$user->signature_key) {
                throw new \Exception('El usuario no tiene una firma electrónica configurada.');
            }

            $p12Content = Storage::disk('private')->get($user->signature_path);
            $password = decrypt($user->signature_key);

            $signedPdfContent = Signature::sign($unsignedPdfContent, $p12Content, $password);

            // 5. Return the signed PDF for download
            return response($signedPdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $claveAcceso . '.pdf"',
            ]);

        } catch (\Exception $e) {
            Log::error('PDF Generation/Signing Failed for ' . $claveAcceso, ['exception' => $e]);
            return response()->json(['error' => 'No se pudo generar el PDF: ' . $e->getMessage()], 500);
        }
    }

    public function getComprobante($claveAcceso, $internal = false)
    {
        try {
            $wsdlUrl = 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantes?wsdl';

            $options = [
                'trace' => true,
                'exceptions' => true,
                'cache_wsdl' => WSDL_CACHE_NONE,
                'stream_context' => stream_context_create([
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ])
            ];

            $client = new SoapClient($wsdlUrl, $options);

            $params = [
                'claveAccesoComprobante' => $claveAcceso
            ];

            $response = $client->autorizacionComprobante($params);
            Log::info('SRI SOAP Response for ' . $claveAcceso, ['response' => $response]);

            if (isset($response->RespuestaAutorizacionComprobante->autorizaciones->autorizacion)) {
                $autorizacion = $response->RespuestaAutorizacionComprobante->autorizaciones->autorizacion;

                // SRI can return a single object or an array of autorizacion
                if (is_array($autorizacion)) {
                    $autorizacion = $autorizacion[0]; // Take the first one
                }

                if (isset($autorizacion->comprobante)) {
                    $xmlContent = $autorizacion->comprobante;
                    if ($internal) {
                        return response($xmlContent, 200);
                    }
                    return response($xmlContent, 200, [
                        'Content-Type' => 'application/xml',
                        'Content-Disposition' => 'attachment; filename="' . $claveAcceso . '.xml"',
                    ]);
                } else {
                     $errorMessage = 'El comprobante no fue encontrado en la respuesta del SRI.';
                     if(isset($autorizacion->mensajes->mensaje->mensaje)) {
                        $errorMessage .= ' Razón: ' . $autorizacion->mensajes->mensaje->mensaje;
                     }
                     return response()->json(['error' => $errorMessage], 404);
                }
            }

            return response()->json(['error' => 'Respuesta inesperada del servicio del SRI.'], 500);

        } catch (\SoapFault $e) {
            Log::error('SRI SOAP Fault for ' . $claveAcceso, ['exception' => $e]);
            return response()->json(['error' => 'Error en el servicio SOAP del SRI: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            Log::error('Unexpected Error for ' . $claveAcceso, ['exception' => $e]);
            return response()->json(['error' => 'Ocurrió un error inesperado: ' . $e->getMessage()], 500);
        }
    }
}
