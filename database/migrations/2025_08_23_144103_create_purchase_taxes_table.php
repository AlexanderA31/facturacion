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
        Schema::create('purchase_taxes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_id');
            $table->string('codigo_impuesto', 1);
            $table->string('codigo_porcentaje', 4);
            $table->decimal('base_imponible', 14, 2);
            $table->decimal('valor', 14, 2);
            $table->timestamps();

            $table->foreign('purchase_id')->references('id')->on('purchase_invoices')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_taxes');
    }
};
