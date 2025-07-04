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
        Schema::create('mesas', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // ✅ UUID sin 'after'
            $table->integer('numero_mesa')->unique();
            $table->enum('estado', ['disponible', 'ocupada'])->default('disponible'); // ✅ Estado sin 'after'
            $table->text('codigo_qr')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mesas');
    }
};
