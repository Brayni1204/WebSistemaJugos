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
                    Schema::create('detalle_gastos', function (Blueprint $table) {
                        $table->id();
                        $table->foreignId('gasto_id')->constrained('gastos')->onDelete('cascade');
                        $table->foreignId('categoria_id')->nullable()->constrained('categorias_gastos')->onDelete('set null');
                        $table->string('producto_nombre');
                        $table->decimal('cantidad', 10, 2);
                        $table->string('unidad_medida'); // Ej: KG, Unidad, Litro
                        $table->decimal('precio_unitario', 10, 2);
                        $table->decimal('precio_total', 10, 2);
                        $table->timestamps();
                    });    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_gastos');
    }
};
