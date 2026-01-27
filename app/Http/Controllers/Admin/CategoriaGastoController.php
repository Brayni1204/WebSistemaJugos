<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategoriaGasto;
use Illuminate\Http\Request;

class CategoriaGastoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categorias = CategoriaGasto::all();
        return view('admin.categorias-gastos.index', compact('categorias'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.categorias-gastos.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:categorias_gastos,nombre',
        ]);

        CategoriaGasto::create($request->all());

        return redirect()->route('admin.categorias-gastos.index')->with('success', 'Categoría de gasto creada con éxito.');
    }

    /**
     * Display the specified resource.
     */
    public function show(CategoriaGasto $categoria)
    {
        return redirect()->route('admin.categorias-gastos.edit', $categoria);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CategoriaGasto $categoria)
    {
        return view('admin.categorias-gastos.edit', ['categoriaGasto' => $categoria]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CategoriaGasto $categoria)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:categorias_gastos,nombre,' . $categoria->id,
        ]);

        $categoria->update($request->all());

        return redirect()->route('admin.categorias-gastos.index')->with('success', 'Categoría de gasto actualizada con éxito.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CategoriaGasto $categoria)
    {
        $categoria->delete();

        return redirect()->route('admin.categorias-gastos.index')->with('success', 'Categoría de gasto eliminada con éxito.');
    }
}
