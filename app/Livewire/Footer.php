<?php

namespace App\Livewire;

use App\Models\Categoria;
use App\Models\Empresa;
use App\Models\MenuBar;
use App\Models\Pagina;
use Livewire\Component;
use Illuminate\Support\Facades\Cache;

class Footer extends Component
{
    public $mostrarTodas = false;

    public function toggleCategorias()
    {
        $this->mostrarTodas = !$this->mostrarTodas;
    }

    public function render()
    {
        $empresa = Cache::remember('footer_empresa_data', 60, function () {
            return Empresa::first();
        });

        $paginas = Cache::remember('footer_paginas_data', 60, function () {
            return Pagina::where('status', 2)->get();
        });

        $categoria = Cache::remember('footer_categoria_data', 60, function () {
            return Categoria::where('status', 1)->orderBy('id', 'desc')->get();
        });

        return view('livewire.footer', compact('empresa', 'paginas', 'categoria'));
    }
}
