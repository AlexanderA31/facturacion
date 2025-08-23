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
        Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('proveedor_id');
            $table->string('tipo_comprobante', 2);
            $table->string('establecimiento', 3);
            $table->string('punto_emision', 3);
            $table->string('secuencial', 9);
            $table->date('fecha_emision');
            $table->date('fecha_registro');
            $table->string('autorizacion', 49);
            $table->enum('parte_relacionada', ['SI', 'NO']);
            $table->string('cod_sustento', 2);
            $table->timestamps();

            $table->foreign('proveedor_id')->references('id')->on('suppliers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_invoices');
    }
};
