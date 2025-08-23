<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WithholdingVatLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'withholding_id',
        'cod_ret_iva',
        'base_imponible',
        'porcentaje',
        'valor',
    ];

    public function withholdingVoucher()
    {
        return $this->belongsTo(WithholdingVoucher::class, 'withholding_id');
    }
}
