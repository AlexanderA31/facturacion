<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'proveedor_id',
        'tipo_comprobante',
        'establecimiento',
        'punto_emision',
        'secuencial',
        'fecha_emision',
        'fecha_registro',
        'autorizacion',
        'parte_relacionada',
        'cod_sustento',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_registro' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'proveedor_id');
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class, 'purchase_id');
    }

    public function taxes()
    {
        return $this->hasMany(PurchaseTax::class, 'purchase_id');
    }

    public function payments()
    {
        return $this->hasMany(PurchasePayment::class, 'purchase_id');
    }

    public function withholdingVoucher()
    {
        return $this->hasOne(WithholdingVoucher::class, 'purchase_id');
    }
}
