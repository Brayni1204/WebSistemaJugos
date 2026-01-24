<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Gasto;
use App\Models\Proveedor;
use App\Models\CategoriaGasto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class GastoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $gastos = Gasto::with(['proveedor', 'user'])->latest()->get();
        return view('admin.gastos.index', compact('gastos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $proveedores = Proveedor::all();
        $categorias = CategoriaGasto::all();
        return view('admin.gastos.create', compact('proveedores', 'categorias'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'fecha_gasto' => 'required|date',
            'total' => 'required|numeric|min:0',
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_nombre' => 'required|string|max:255',
            'detalles.*.cantidad' => 'required|numeric|min:0',
            'detalles.*.unidad_medida' => 'required|string|max:50',
            'detalles.*.precio_unitario' => 'required|numeric|min:0',
            'detalles.*.precio_total' => 'required|numeric|min:0',
            'detalles.*.categoria_id' => 'nullable|exists:categorias_gastos,id',
        ]);

        DB::transaction(function () use ($request) {
            $gasto = Gasto::create([
                'proveedor_id' => $request->proveedor_id,
                'user_id' => Auth::id(),
                'fecha_gasto' => $request->fecha_gasto,
                'comprobante_tipo' => $request->comprobante_tipo,
                'comprobante_numero' => $request->comprobante_numero,
                'total' => $request->total,
                'observacion' => $request->observacion,
            ]);

            foreach ($request->detalles as $detalle) {
                $gasto->detalles()->create($detalle);
            }
        });

        return redirect()->route('admin.gastos.index')->with('success', 'Gasto registrado con éxito.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Gasto $gasto)
    {
        $gasto->load(['proveedor', 'user', 'detalles.categoria']);
        return view('admin.gastos.show', compact('gasto'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Gasto $gasto)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Gasto $gasto)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Gasto $gasto)
    {
        $gasto->delete();
        return redirect()->route('admin.gastos.index')->with('success', 'Gasto eliminado con éxito.');
    }
}
