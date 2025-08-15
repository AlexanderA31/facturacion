<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SoapClient;

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

            if (isset($response->RespuestaAutorizacionComprobante->autorizaciones->autorizacion->comprobante)) {
                $xmlContent = $response->RespuestaAutorizacionComprobante->autorizaciones->autorizacion->comprobante;
                return response($xmlContent, 200, [
                    'Content-Type' => 'application/xml',
                    'Content-Disposition' => 'attachment; filename="' . $claveAcceso . '.xml"',
                ]);
            } else {
                $errorMessage = 'No se pudo obtener el comprobante del SRI. Razón: ' . ($response->RespuestaAutorizacionComprobante->autorizaciones->autorizacion->mensajes->mensaje->mensaje ?? 'Desconocida');
                return response()->json(['error' => $errorMessage], 404);
            }

        } catch (\SoapFault $e) {
            return response()->json(['error' => 'Error en el servicio SOAP del SRI: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error inesperado: ' . $e->getMessage()], 500);
        }
    }
}
