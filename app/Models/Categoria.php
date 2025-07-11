<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use HasFactory;
    protected $fillable = ['nombre_categoria', 'descripcion', 'status'];
    public function productos()
    {
        return $this->hasMany(Producto::class, 'id_categoria');
    }
    public function getStatusTextAttribute()
    {
        return $this->status == 1 ? 'Activo' : 'Inactivo';
    }
    public function image()
    {
        return $this->morphMany(Image::class, 'imageable');
    }
}
