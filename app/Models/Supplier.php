<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'tipo_id',
        'identificacion',
        'razon_social',
    ];

    public function purchaseInvoices()
    {
        return $this->hasMany(PurchaseInvoice::class);
    }

    public function withholdingVouchers()
    {
        return $this->hasMany(WithholdingVoucher::class);
    }
}
