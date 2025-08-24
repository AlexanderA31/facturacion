<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tipo_id',
        'identificacion',
        'razon_social',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function purchaseInvoices()
    {
        return $this->hasMany(PurchaseInvoice::class);
    }

    public function withholdingVouchers()
    {
        return $this->hasMany(WithholdingVoucher::class);
    }
}
