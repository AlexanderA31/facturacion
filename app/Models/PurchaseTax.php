<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseTax extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'codigo_impuesto',
        'codigo_porcentaje',
        'base_imponible',
        'valor',
    ];

    public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_id');
    }
}
