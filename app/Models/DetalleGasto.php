<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleGasto extends Model
{
    use HasFactory;

    protected $fillable = [
        'gasto_id',
        'categoria_id',
        'producto_nombre',
        'cantidad',
        'unidad_medida',
        'precio_unitario',
        'precio_total'
    ];

    public function gasto()
    {
        return $this->belongsTo(Gasto::class);
    }

    public function categoria()
    {
        return $this->belongsTo(CategoriaGasto::class);
    }
}
