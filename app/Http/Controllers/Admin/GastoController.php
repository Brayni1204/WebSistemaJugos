<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gasto;
use App\Models\Proveedor;
use App\Models\CategoriaGasto;
use App\Models\User;
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
        $gastos = Gasto::with(['proveedor', 'user', 'empleado'])->latest()->get();
        return view('admin.gastos.index', compact('gastos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $proveedores = Proveedor::all();
        $categorias = CategoriaGasto::all();
        // Filtrar usuarios que tengan cualquier rol en el sistema
        $empleados = User::whereHas('roles')->get();
        return view('admin.gastos.create', compact('proveedores', 'categorias', 'empleados'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validación dinámica según el tipo de beneficiario
        $rules = [
            'tipo_beneficiario' => 'required|in:proveedor,empleado',
            'fecha_gasto' => 'required|date',
            'observacion' => 'nullable|string|max:255',
        ];

        if ($request->tipo_beneficiario === 'proveedor') {
            $rules['proveedor_id'] = 'required|exists:proveedores,id';
            $rules['total'] = 'required|numeric|min:0';
            $rules['detalles'] = 'required|array|min:1';
            $rules['detalles.*.producto_nombre'] = 'required|string|max:255';
            $rules['detalles.*.cantidad'] = 'required|numeric|min:0';
            $rules['detalles.*.unidad_medida'] = 'required|string|max:50';
            $rules['detalles.*.precio_unitario'] = 'required|numeric|min:0';
            $rules['detalles.*.precio_total'] = 'required|numeric|min:0';
        } else {
            $rules['empleado_id'] = 'required|exists:users,id';
            $rules['concepto_pago'] = 'required|string|max:255';
            $rules['monto_pago'] = 'required|numeric|min:0.01';
        }

        $request->validate($rules);

        DB::transaction(function () use ($request) {
            $dataGasto = [
                'user_id' => Auth::id(),
                'fecha_gasto' => $request->fecha_gasto,
                'observacion' => $request->observacion,
            ];

            if ($request->tipo_beneficiario === 'proveedor') {
                $dataGasto['proveedor_id'] = $request->proveedor_id;
                $dataGasto['comprobante_tipo'] = $request->comprobante_tipo;
                $dataGasto['comprobante_numero'] = $request->comprobante_numero;
                $dataGasto['total'] = $request->total;
                
                $gasto = Gasto::create($dataGasto);
                
                foreach ($request->detalles as $detalle) {
                    $gasto->detalles()->create($detalle);
                }
            } else {
                // Lógica para empleados
                $dataGasto['empleado_id'] = $request->empleado_id;
                $dataGasto['total'] = $request->monto_pago;
                $dataGasto['comprobante_tipo'] = 'Recibo'; // Por defecto para internos
                
                $gasto = Gasto::create($dataGasto);

                // Creamos un detalle único automático para mantener consistencia
                $gasto->detalles()->create([
                    'producto_nombre' => $request->concepto_pago,
                    'cantidad' => 1,
                    'unidad_medida' => 'servicio',
                    'precio_unitario' => $request->monto_pago,
                    'precio_total' => $request->monto_pago,
                    // 'categoria_id' => null // O asignar una categoría por defecto si existe "Sueldos"
                ]);
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
