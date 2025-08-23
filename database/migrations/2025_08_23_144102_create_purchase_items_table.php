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
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_id');
            $table->string('descripcion', 300);
            $table->decimal('cantidad', 18, 6);
            $table->decimal('precio_unitario', 18, 6);
            $table->decimal('descuento', 18, 6);
            $table->timestamps();

            $table->foreign('purchase_id')->references('id')->on('purchase_invoices')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
