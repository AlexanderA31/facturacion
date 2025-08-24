<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SupplierPolicy
{
    public function view(User $user, Supplier $supplier): Response
    {
        if ($user->id !== $supplier->user_id) {
            return Response::denyWithStatus(404, 'El proveedor no pertenece al usuario autenticado.');
        }
        return Response::allow();
    }

    public function update(User $user, Supplier $supplier): Response
    {
        return $this->view($user, $supplier);
    }

    public function delete(User $user, Supplier $supplier): Response
    {
        return $this->view($user, $supplier);
    }
}
