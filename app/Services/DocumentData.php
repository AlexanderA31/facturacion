<?php

namespace App\Services;

use App\Models\PuntoEmision;
use App\Models\User;

class DocumentData
{
    public function prepareData(PuntoEmision $puntoEmision, User $user, array $validatedData)
    {
        try {
            // Bloqueo y actualización del secuencial
            $puntoEmision->refresh();

            $ultimoSecuencial = (int) $puntoEmision->ultimoSecuencial;
            $nuevoSecuencial = null;
            $maxIntentos = 5000; // Límite para evitar bucles infinitos

            for ($i = 0; $i < $maxIntentos; $i++) {
                $ultimoSecuencial++;
                $secuencialCandidato = str_pad($ultimoSecuencial, 9, '0', STR_PAD_LEFT);

                $comprobanteExistente = \App\Models\Comprobante::where('establecimiento', $puntoEmision->establecimiento->numero)
                    ->where('punto_emision', $puntoEmision->numero)
                    ->where('secuencial', $secuencialCandidato)
                    ->where('ambiente', $user->ambiente)
                    ->whereIn('estado', [\App\Enums\EstadosComprobanteEnum::AUTORIZADO->value, \App\Enums\EstadosComprobanteEnum::FIRMADO->value])
                    ->exists();

                if (!$comprobanteExistente) {
                    $nuevoSecuencial = $secuencialCandidato;
                    break;
                }
            }

            if (is_null($nuevoSecuencial)) {
                throw new \Exception("No se pudo encontrar un secuencial disponible después de {$maxIntentos} intentos.");
            }

            $validatedData['secuencial'] = $nuevoSecuencial;
            $validatedData['estab'] = $puntoEmision->establecimiento->numero;
            $validatedData['ptoEmi'] = $puntoEmision->numero;
            $validatedData['dirEstablecimiento'] = $puntoEmision->establecimiento->direccion;

            $validatedData['ambiente'] = $user->ambiente;
            $validatedData['ruc'] = $user->ruc;
            $validatedData['razonSocial'] = $user->razonSocial;
            $validatedData['nombreComercial'] = $user->nombreComercial;
            $validatedData['dirMatriz'] = $user->dirMatriz;
            $validatedData['contribuyenteEspecial'] = $user->contribuyenteEspecial;
            $validatedData['obligadoContabilidad'] = $user->obligadoContabilidad ? 'SI' : 'NO';

            return $validatedData;
        } catch(\Exception $e) {
            throw new \Exception('Error al obtener los datos del punto de emisión: ' . $e->getMessage());
        }
    }
}