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
        Schema::create('withholding_vouchers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('proveedor_id');
            $table->string('establecimiento', 3);
            $table->string('punto_emision', 3);
            $table->string('secuencial', 9);
            $table->string('autorizacion', 49);
            $table->date('fecha_emision');
            $table->unsignedBigInteger('purchase_id')->nullable();
            $table->timestamps();

            $table->foreign('proveedor_id')->references('id')->on('suppliers')->onDelete('cascade');
            $table->foreign('purchase_id')->references('id')->on('purchase_invoices')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withholding_vouchers');
    }
};
