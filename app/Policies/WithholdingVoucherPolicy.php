<?php

namespace App\Policies;

use App\Models\WithholdingVoucher;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class WithholdingVoucherPolicy
{
    public function view(User $user, WithholdingVoucher $withholdingVoucher): Response
    {
        if ($user->id !== $withholdingVoucher->user_id) {
            return Response::denyWithStatus(404, 'La retención no pertenece al usuario autenticado.');
        }
        return Response::allow();
    }

    public function update(User $user, WithholdingVoucher $withholdingVoucher): Response
    {
        return $this->view($user, $withholdingVoucher);
    }

    public function delete(User $user, WithholdingVoucher $withholdingVoucher): Response
    {
        return $this->view($user, $withholdingVoucher);
    }
}
