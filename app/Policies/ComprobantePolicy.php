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
        // The logic for downloading should depend on the final status of the invoice,
        // not on whether an error code was logged at some point during the process.
        // The controller will handle checking the 'autorizado' status.
        // Here, we just check for ownership.
        if (!$this->isUserOwner($user, $comprobante)) {
            return Response::deny('No tienes permiso para ver este comprobante.');
        }

        return Response::allow();
    }
}
