<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pagina;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PaginaController extends Controller
{

    public function index()
    {
        $pagina = Pagina::all();
        return view('admin.paginas.index', compact('pagina'));
    }
    public function create()
    {
        return view('admin.paginas.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'titulo_paginas' => 'required|max:255|unique:paginas,titulo_paginas',
            'slug' => 'required|unique:paginas,slug',
            'resumen' => 'nullable|string',
            'imagen' => 'nullable|image',
        ]);


        DB::beginTransaction();
        try {
            // Crear la página
            $pagina = Pagina::create([
                'titulo_paginas' => $request->titulo_paginas,
                'slug' => $request->slug,
                'resumen' => $request->resumen,
            ]);

            // Guardar imagen si el usuario sube una
            if ($request->hasFile('imagen')) {
                $url = $request->file('imagen')->store('Pagina', 'public');
                $pagina->image_pagina()->create(['url' => $url]);
            }

            DB::commit();
            return redirect()->route('admin.paginas.show', $pagina)->with('info', 'Página creada correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al crear la página.']);
        }
    }

    public function show(Pagina $pagina)
    {
        return view('admin.paginas.show', compact('pagina'));
    }

    public function edit(Pagina $pagina)
    {
        return view('admin.paginas.edit', compact('pagina'));
    }
     
    public function update(Request $request, Pagina $pagina)
    {

        // Verificamos si solo se quiere cambiar el estado
        if ($request->has('status')) {
            $pagina->update(['status' => $request->status]);

            return redirect()->route('admin.paginas.show', $pagina)
                ->with('info', 'El estado de la página ha sido actualizado.');
        }

        // Si no es solo el estado, validamos los datos del formulario de edición
        $request->validate([
            'titulo_paginas' => 'required|max:255|unique:paginas,titulo_paginas,' . $pagina->id,
            'slug' => 'required|unique:paginas,slug,' . $pagina->id,
            'resumen' => 'nullable|string',
            'imagen' => 'nullable|image',
        ]);

        DB::beginTransaction();
        try {
            // Actualizar solo los datos proporcionados por el usuario
            $pagina->update($request->only(['titulo_paginas', 'slug', 'resumen']));

            // Si se subió una nueva imagen, eliminar la anterior y guardar la nueva
            if ($request->hasFile('imagen')) {
                if ($pagina->image_pagina) {
                    Storage::disk('public')->delete($pagina->image_pagina->url);
                    $pagina->image_pagina()->delete();
                }

                $url = $request->file('imagen')->store('Pagina', 'public');
                $pagina->image_pagina()->create(['url' => $url]);
            }

            DB::commit();
            return redirect()->route('admin.paginas.show', $pagina)
                ->with('info', 'Página actualizada correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al actualizar la página.']);
        }
    }

    public function destroy(Pagina $pagina)
    {
        // Eliminar imagen si existe
        if ($pagina->image_pagina) {
            Storage::disk('public')->delete($pagina->image_pagina->url);
            $pagina->image_pagina()->delete();
        }

        // Eliminar la página
        $pagina->delete();

        return response()->json(['success' => 'Página eliminada correctamente.']);
    }
}
