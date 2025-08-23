<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('withholding_vat_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('withholding_id');
            $table->string('cod_ret_iva', 5);
            $table->decimal('base_imponible', 14, 2);
            $table->decimal('porcentaje', 5, 2);
            $table->decimal('valor', 14, 2);
            $table->timestamps();

            $table->foreign('withholding_id')->references('id')->on('withholding_vouchers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public a` down(): void
    {
        Schema::dropIfExists('withholding_vat_lines');
    }
};
