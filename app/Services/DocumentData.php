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

            // Obtener el último secuencial usado desde la tabla de comprobantes para este punto de emisión
            $ultimoSecuencialComprobantes = \App\Models\Comprobante::where('establecimiento', $puntoEmision->establecimiento->numero)
                ->where('punto_emision', $puntoEmision->numero)
                ->where('ambiente', $user->ambiente)
                ->max('secuencial');

            // Comparar con el último secuencial del punto de emisión y tomar el mayor
            $ultimoSecuencialBase = max((int)$ultimoSecuencialComprobantes, (int)$puntoEmision->ultimoSecuencial);

            // Generar el nuevo secuencial
            $nuevoSecuencial = str_pad($ultimoSecuencialBase + 1, 9, '0', STR_PAD_LEFT);

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