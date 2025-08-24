<?php

namespace App\Policies;

use App\Models\PurchaseInvoice;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PurchaseInvoicePolicy
{
    public function view(User $user, PurchaseInvoice $purchaseInvoice): Response
    {
        if ($user->id !== $purchaseInvoice->user_id) {
            return Response::denyWithStatus(404, 'La factura de compra no pertenece al usuario autenticado.');
        }
        return Response::allow();
    }

    public function update(User $user, PurchaseInvoice $purchaseInvoice): Response
    {
        return $this->view($user, $purchaseInvoice);
    }

    public function delete(User $user, PurchaseInvoice $purchaseInvoice): Response
    {
        return $this->view($user, $purchaseInvoice);
    }
}
