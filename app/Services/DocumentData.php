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
            $puntoEmision->refresh(); // Por si acaso hay cambios previos

            $ultimoSecuencial = $puntoEmision->ultimoSecuencial + 1;
            $nuevoSecuencial = str_pad($ultimoSecuencial, 9, '0', STR_PAD_LEFT);

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