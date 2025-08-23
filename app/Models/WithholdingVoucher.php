<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WithholdingVoucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'proveedor_id',
        'establecimiento',
        'punto_emision',
        'secuencial',
        'autorizacion',
        'fecha_emision',
        'purchase_id',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'proveedor_id');
    }

    public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_id');
    }

    public function incomeLines()
    {
        return $this->hasMany(WithholdingIncomeLine::class, 'withholding_id');
    }

    public function vatLines()
    {
        return $this->hasMany(WithholdingVatLine::class, 'withholding_id');
    }
}
