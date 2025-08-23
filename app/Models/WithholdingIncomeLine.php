<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WithholdingIncomeLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'withholding_id',
        'cod_ret_air',
        'base_imponible',
        'porcentaje',
        'valor',
    ];

    public function withholdingVoucher()
    {
        return $this->belongsTo(WithholdingVoucher::class, 'withholding_id');
    }
}
