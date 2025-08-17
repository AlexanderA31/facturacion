<?php

namespace App\Services;

use App\Exceptions\SriException;
use SoapClient;
use SoapFault;
use Illuminate\Support\Facades\Log;


class SriComprobanteService
{
    // URLs para pruebas y producción
    private const RECEPCION_PRUEBAS = 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl';
    private const RECEPCION_PRODUCCION = 'https://cel.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl';
    private const AUTORIZACION_PRUEBAS = 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl';
    private const AUTORIZACION_PRODUCCION = 'https://cel.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl';


    /**
     * Verifica la disponibilidad del servicio SRI.
     * @param string $wsdl
     * @return bool
     */
    private function checkSriDisponible(string $wsdl)
    {
        try {
            // Intentar una conexión simple al WSDL con un timeout corto
            $client = new SoapClient($wsdl, [
                'connection_timeout' => 5, // Timeout de 5 segundos
                'exceptions' => true,
            ]);

            // Si la conexión al WSDL es exitosa, asumimos que el servicio está disponible
            return true;
        } catch (SoapFault $e) {
            Log::error("🚫 SRI no disponible (WSDL: {$wsdl}): {$e->getMessage()}");
            return false;
        } catch (\Exception $e) {
            Log::error("🚫 Error al verificar disponibilidad del SRI (WSDL: {$wsdl}): {$e->getMessage()}");
            return false;
        }
    }


    /**
     * Envía un comprobante al SRI para su recepción.
     * @param string $xmlString
     * @return array
     * @throws SriException
     */
    public function enviarComprobanteRecepcion(string $xmlString)
    {
        Log::info('⏳ Enviando comprobante a la recepción');

        // Detectar el ambiente desde el XML
        $ambiente = $this->leerAmbienteDesdeXml($xmlString);

        // Configurar la URL según el ambiente
        $wsdl = ($ambiente == '1') ? self::RECEPCION_PRUEBAS : self::RECEPCION_PRODUCCION;

        // Verificar la disponibilidad del servicio SRI
        if (!$this->checkSriDisponible($wsdl)) {
            throw new SriException('0', 'El servicio SRI no está disponible.');
        }

        try {
            // Cliente SOAP
            $client = new SoapClient($wsdl);

            // Parámetros para el servicio SOAP
            $params = (object) ['xml' => $xmlString];
            $result = $client->validarComprobante($params);

            $estado = $result->RespuestaRecepcionComprobante->estado ?? null;

            if ($estado !== 'RECIBIDA') {
                // Extraer el primer mensaje de error del SRI
                $mensaje = $result->RespuestaRecepcionComprobante->comprobantes->comprobante->mensajes->mensaje ?? null;

                $codigo = $mensaje->identificador ?? '0';
                $descripcion = $mensaje->mensaje ?? 'Error en recepción';
                $informacionAdicional = $mensaje->informacionAdicional ?? null;

                throw new SriException($codigo, $descripcion, [
                    'info_adicional' => $informacionAdicional,
                    'estado_sri' => $estado,
                ]);
            }

            return [
                'success' => true,
                'response' => $result
            ];
        } catch (SoapFault $e) {
            throw new SriException('0', 'Error de conexión con el SRI: ' . $e->getMessage());
        } catch (SriException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new SriException('0', 'Error inesperado en recepción: ' . $e->getMessage());
        }
    }


    /**
     * Envía un comprobante al SRI para autorización.
     * @param string $claveAcceso
     * @param string $ambiente
     * @return array
     * @throws SriException
     */
    private function findAuthorized(object $response)
    {
        $autorizacionesNode = data_get($response, 'RespuestaAutorizacionComprobante.autorizaciones');

        if (!$autorizacionesNode || !isset($autorizacionesNode->autorizacion)) {
            return null;
        }

        $autorizaciones = is_array($autorizacionesNode->autorizacion)
            ? $autorizacionesNode->autorizacion
            : [$autorizacionesNode->autorizacion];

        foreach ($autorizaciones as $auth) {
            if (data_get($auth, 'estado') === 'AUTORIZADO') {
                return $auth;
            }
        }

        // Si no se encuentra ninguna autorización 'AUTORIZADO', se puede lanzar una excepción
        // con el mensaje de la primera autorización encontrada, si existe.
        $primeraAutorizacion = $autorizaciones[0] ?? null;
        if ($primeraAutorizacion) {
            $mensaje = data_get($primeraAutorizacion, 'mensajes.mensaje');
            $codigo = data_get($mensaje, 'identificador', '0');
            $descripcion = data_get($mensaje, 'mensaje', 'Comprobante no autorizado');
            $infoAdicional = data_get($mensaje, 'informacionAdicional');

            throw new SriException($codigo, $descripcion, [
                'info_adicional' => $infoAdicional,
                'estado_sri' => data_get($primeraAutorizacion, 'estado'),
            ]);
        }

        return null;
    }

    public function enviarComprobanteAutorizacion(string $claveAcceso, string $ambiente = '1')
    {
        Log::info('⏳ Enviando comprobante para autorización');
        $wsdl = $ambiente === '1' ? self::AUTORIZACION_PRUEBAS : self::AUTORIZACION_PRODUCCION;

        if (!$this->checkSriDisponible($wsdl)) {
            throw new SriException('0', 'El servicio del SRI no está disponible en este momento.');
        }

        try {
            $client = new SoapClient($wsdl);
            $params = (object) ['claveAccesoComprobante' => $claveAcceso];
            $maxIntentos = 5;

            for ($intentos = 1; $intentos <= $maxIntentos; $intentos++) {
                try {
                    $result = $client->autorizacionComprobante($params);
                    Log::info("🔁 Intento {$intentos} autorización: " . json_encode($result));

                    $autorizacionAutorizada = $this->findAuthorized($result);

                    if ($autorizacionAutorizada) {
                        return [
                            'success' => true,
                            'autorizacion' => $autorizacionAutorizada,
                            'mensajes' => data_get($autorizacionAutorizada, 'mensajes'),
                        ];
                    }

                    sleep(1); // Espera antes del siguiente intento
                } catch (SriException $e) {
                    // La excepción ya fue lanzada por findAuthorized con los detalles correctos
                    throw $e;
                } catch (SoapFault $e) {
                    throw new SriException('0', 'Error de conexión con el SRI: ' . $e->getMessage());
                } catch (\Exception $e) {
                    if ($intentos >= $maxIntentos) {
                        throw new SriException('0', 'Error inesperado en autorización: ' . $e->getMessage());
                    }
                    sleep(1);
                }
            }

            throw new SriException('0', 'No se recibió una respuesta de autorización válida del SRI después de varios intentos.');
        } catch (SoapFault $e) {
            throw new SriException('0', 'Error de conexión con el SRI: ' . $e->getMessage());
        }
    }


    /**
     * Envía un comprobante al SRI y lo autoriza.
     * @param string $xmlString
     * @param string $claveAcceso
     * @return array
     * @throws SriException
     */
    public function enviarYAutorizarComprobante(string $xmlString, string $claveAcceso): array
    {
        try {
            // 👉 Enviar a recepción
            $recepcion = $this->enviarComprobanteRecepcion($xmlString);

            if (!$recepcion['success']) {
                $estado = $recepcion['estado'] ?? null;
                $mensajes = $recepcion['mensajes'] ?? null;

                $codigo = '0';
                $mensaje = 'El comprobante no fue recibido';

                // Intentar extraer código del primer mensaje de error del SRI
                if (is_object($mensajes)) {
                    $msg = is_array($mensajes) ? $mensajes[0] : $mensajes->mensaje ?? null;

                    if ($msg) {
                        $codigo = $msg->identificador ?? '0';
                        $mensaje = $msg->mensaje ?? $mensaje;
                    }
                }

                throw new SriException($codigo, $mensaje, [
                    'estado' => $estado,
                    'mensajes' => $mensajes
                ]);
            }

            // 👉 Autorización
            return $this->enviarComprobanteAutorizacion($claveAcceso);

        } catch (SriException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new SriException('0', 'Error inesperado al enviar y autorizar comprobante: ' . $e->getMessage());
        }
    }


    /**
     * Consulta el XML autorizado desde el SRI.
     *
     * @param string $claveAcceso Clave de acceso del comprobante
     * @param string $ambiente Ambiente (1: pruebas, 2: producción)
     * @return string XML autorizado
     * @throws SriException
     */
    public function consultarXmlAutorizado(string $claveAcceso, string $ambiente = '1'): string
    {
        Log::info("⏳ Consultando XML autorizado para clave de acceso: {$claveAcceso}");

        $wsdl = ($ambiente === '1') ? self::AUTORIZACION_PRUEBAS : self::AUTORIZACION_PRODUCCION;

        if (!$this->checkSriDisponible($wsdl)) {
            throw new SriException('0', 'El servicio del SRI no está disponible en este momento.');
        }

        try {
            $client = new SoapClient($wsdl);
            $params = (object) ['claveAccesoComprobante' => $claveAcceso];

            $result = $client->autorizacionComprobante($params);
            Log::info("Respuesta completa del SRI: " . print_r($result, true));

            $autorizacionAutorizada = $this->findAuthorized($result);

            if ($autorizacionAutorizada) {
                // El campo 'comprobante' es un string CDATA que contiene el XML
                $xmlString = data_get($autorizacionAutorizada, 'comprobante');
                if (is_string($xmlString)) {
                    return $xmlString;
                }
            }

            throw new SriException('0', 'No se encontró un comprobante autorizado en la respuesta del SRI.');
        } catch (SriException $e) {
            // Re-lanzar la excepción de findAuthorized
            throw $e;
        } catch (SoapFault $e) {
            throw new SriException('0', 'Error de conexión con el SRI: ' . $e->getMessage());
        } catch (\Exception $e) {
            throw new SriException('0', 'Error inesperado al consultar el XML: ' . $e->getMessage());
        }
    }


    /**
     * Lee el ambiente desde el XML.
     * @param string $xmlString
     * @return string
     * @throws \Exception
     */
    private function leerAmbienteDesdeXml(string $xmlString): string
    {
        $xml = simplexml_load_string($xmlString);
        $ambiente = (string) $xml->infoTributaria->ambiente;

        if (!in_array($ambiente, ['1', '2'])) {
            throw new \Exception("Ambiente inválido: $ambiente");
        }

        return $ambiente;
    }
}
