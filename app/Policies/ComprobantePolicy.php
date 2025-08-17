<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class ComprobantePolicy
{
    private function isUserOwner(User $user, $comprobante): bool
    {
        return $comprobante->user_id === $user->id;
    }

    public function view(User $user, $comprobante)
    {
        // Verificar si el usuario es el propietario del comprobante
        if (!$this->isUserOwner($user, $comprobante)) {
            return Response::deny('No tienes permiso para ver este comprobante.');
        }

        return Response::allow();
    }

    public function viewXML(User $user, $comprobante)
    {
        // Verificar si el usuario es el propietario del comprobante
        if (!$this->isUserOwner($user, $comprobante)) {
            return Response::deny('No tienes permiso para ver este comprobante.');
        }

        // Permitir la consulta si es un error de duplicado, para poder intentar el fallback.
        if ($comprobante->error_code && $comprobante->error_message !== 'ERROR SECUENCIAL REGISTRADO') {
            return Response::deny('El comprobante tiene un error que no permite la consulta directa.');
        }

        return Response::allow();
    }
}