<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SoapClient;
use Illuminate\Support\Facades\Log;

class SriProxyController extends Controller
{
    public function getComprobante($claveAcceso)
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
