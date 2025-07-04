<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\DetalleVenta;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CajaController extends Controller
{
    public function index()
    {
        return view('admin.reportes.index');
    }
    private function obtenerEstadisticas($ventas)
    {
        // Obtener el producto más vendido
        $productoMasVendido = DB::table('productos')
            ->select('productos.nombre_producto') // Especifica la tabla
            ->join('detalle_ventas', 'productos.id', '=', 'detalle_ventas.producto_id') // Usa producto_id en lugar de id_producto
            ->whereIn('detalle_ventas.venta_id', $ventas->pluck('id'))
            ->groupBy('productos.id', 'productos.nombre_producto') // Agrupa correctamente
            ->orderByRaw('SUM(detalle_ventas.cantidad) DESC')
            ->limit(1)
            ->pluck('productos.nombre_producto') // Extrae el nombre del producto
            ->first() ?? 'N/A';

        // Obtener clientes frecuentes
        $clientesFrecuentes = DB::table('clientes')
            ->select('clientes.nombre', DB::raw('COUNT(*) as total_compras'))
            ->join('ventas', 'clientes.id', '=', 'ventas.cliente_id')
            ->whereIn('ventas.id', $ventas->pluck('id'))
            ->groupBy('clientes.id', 'clientes.nombre') // Agrega 'clientes.nombre' aquí
            ->orderBy('total_compras', 'DESC')
            ->limit(5)
            ->get();

        return [
            'producto_mas_vendido' => $productoMasVendido,
            'clientes_frecuentes' => $clientesFrecuentes
        ];
    }


    // 🔹 Reporte diario
    public function reporteDiario(Request $request)
    {
        try {
            $fecha = $request->input('fecha');

            // Verifica que la fecha esté presente
            if (!$fecha) {
                return response()->json(['error' => 'Fecha requerida'], 400);
            }

            // Lógica para obtener los datos del reporte (ejemplo)
            $ventas = Venta::whereDate('created_at', $fecha)->sum('total_pago');
            $productoMasVendido = Producto::select('productos.nombre_producto') // Especifica la tabla
                ->join('detalle_ventas', 'productos.id', '=', 'detalle_ventas.producto_id')
                ->groupBy('productos.id', 'productos.nombre_producto') // Agrupa correctamente
                ->orderByRaw('SUM(detalle_ventas.cantidad) DESC')
                ->limit(1)
                ->pluck('productos.nombre_producto') // Especifica la tabla aquí también
                ->first();


            $clientesFrecuentes = Cliente::select('clientes.nombre', DB::raw('COUNT(*) as total_compras'))
                ->join('ventas', 'clientes.id', '=', 'ventas.cliente_id')
                ->groupBy('clientes.id', 'clientes.nombre') // Agrega 'clientes.nombre' aquí
                ->orderBy('total_compras', 'DESC')
                ->limit(5)
                ->get();


            return response()->json([
                'total_ventas' => $ventas,
                'producto_mas_vendido' => $productoMasVendido ?? 'N/A',
                'clientes_frecuentes' => $clientesFrecuentes
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al generar el reporte', 'message' => $e->getMessage()], 500);
        }
    }


    // 🔹 Reporte semanal
    public function reporteSemanal(Request $request)
    {
        try {
            if (!$request->has('inicio_semana')) {
                return response()->json(['error' => 'Fecha de inicio de semana requerida'], 400);
            }

            $inicioSemana = Carbon::parse($request->input('inicio_semana'))->startOfWeek();
            $finSemana = $inicioSemana->copy()->endOfWeek();

            $ventas = Venta::whereBetween('created_at', [$inicioSemana, $finSemana])->with('detalles')->get();
            $totalVentas = $ventas->sum('total_pago');

            $estadisticas = $ventas->isNotEmpty() ? $this->obtenerEstadisticas($ventas) : [
                'producto_mas_vendido' => 'N/A',
                'clientes_frecuentes' => [] // Asegurar que siempre haya un array
            ];

            return response()->json([
                'rango' => $inicioSemana->toDateString() . ' -> ' . $finSemana->toDateString(),
                'total_ventas' => $totalVentas,
                'producto_mas_vendido' => $estadisticas['producto_mas_vendido'],
                'clientes_frecuentes' => $estadisticas['clientes_frecuentes'] // Asegurar que esté definido
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al generar el reporte', 'message' => $e->getMessage()], 500);
        }
    }


    // 🔹 Reporte mensual
    public function reporteMensual(Request $request)
    {
        $mes = $request->input('mes');
        $anio = $request->input('anio', Carbon::now()->year); // Año actual por defecto

        $ventas = Venta::whereYear('created_at', $anio)
            ->whereMonth('created_at', $mes)
            ->with('detalles')
            ->get();
        $totalVentas = $ventas->sum('total_pago');

        $estadisticas = $this->obtenerEstadisticas($ventas);

        return response()->json([
            'mes' => $mes,
            'anio' => $anio,
            'total_ventas' => $totalVentas,
            'producto_mas_vendido' => $estadisticas['producto_mas_vendido'],
            'clientes_frecuentes' => $estadisticas['clientes_frecuentes']
        ]);
    }

    // 🔹 Reporte por rango de fechas
    public function reportePorRango(Request $request)
    {
        $fechaInicio = Carbon::parse($request->input('fecha_inicio'));
        $fechaFin = Carbon::parse($request->input('fecha_fin'))->endOfDay();

        $ventas = Venta::whereBetween('created_at', [$fechaInicio, $fechaFin])->with('detalles')->get();
        $totalVentas = $ventas->sum('total_pago');

        $estadisticas = $this->obtenerEstadisticas($ventas);

        return response()->json([
            'rango' => "$fechaInicio -> $fechaFin",
            'total_ventas' => $totalVentas,
            'producto_mas_vendido' => $estadisticas['producto_mas_vendido'],
            'clientes_frecuentes' => $estadisticas['clientes_frecuentes']
        ]);
    }

    /*  public function ventasPorDia(Request $request)
    {
        try {
            $fecha = $request->input('fecha', Carbon::now()->toDateString());

            $ventas = Pedido::whereDate('created_at', $fecha)
                ->where('estado', 'Completado')
                ->selectRaw('COALESCE(SUM(total_pago), 0) as total_ventas, COUNT(*) as cantidad_pedidos')
                ->first();

            return response()->json([
                'fecha' => $fecha,
                'total_ventas' => floatval($ventas->total_ventas), // ✅ Convertimos a número
                'cantidad_pedidos' => intval($ventas->cantidad_pedidos),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error en ventasPorDia: ' . $e->getMessage()
            ], 500);
        }
    }


    public function frecuenciaClientes()
    {
        try {
            $clientes = DB::table('ventas')
                ->join('clientes', 'ventas.cliente_id', '=', 'clientes.id')
                ->select('clientes.nombre', 'clientes.apellidos', 'clientes.email', DB::raw('COUNT(ventas.id) as compras_realizadas'))
                ->groupBy('clientes.id', 'clientes.nombre', 'clientes.apellidos', 'clientes.email')
                ->orderByDesc('compras_realizadas')
                ->get();

            return response()->json(['clientes' => $clientes]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error en frecuenciaClientes: ' . $e->getMessage()
            ], 500);
        }
    }

    public function productosMasVendidos(Request $request)
    {
        try {
            $fechaInicio = $request->input('fecha_inicio', Carbon::now()->startOfMonth()->toDateString());
            $fechaFin = $request->input('fecha_fin', Carbon::now()->toDateString());

            $productos = DB::table('detalle_ventas')
                ->join('productos', 'detalle_ventas.producto_id', '=', 'productos.id')
                ->select('productos.nombre_producto', DB::raw('SUM(detalle_ventas.cantidad) as total_vendido'))
                ->whereBetween('detalle_ventas.created_at', [$fechaInicio, $fechaFin])
                ->groupBy('productos.nombre_producto')
                ->orderByDesc('total_vendido')
                ->get();

            return response()->json(['productos' => $productos]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error en productosMasVendidos: ' . $e->getMessage()
            ], 500);
        }
    }

    public function reporteVentas(Request $request)
    {
        try {
            $tipo = $request->input('tipo', 'diario'); // 'diario', 'semanal', 'mensual'
            $fecha = Carbon::now();

            switch ($tipo) {
                case 'semanal':
                    $inicio = $fecha->startOfWeek()->toDateString();
                    $fin = $fecha->endOfWeek()->toDateString();
                    break;
                case 'mensual':
                    $inicio = $fecha->startOfMonth()->toDateString();
                    $fin = $fecha->endOfMonth()->toDateString();
                    break;
                default:
                    $inicio = $fecha->toDateString();
                    $fin = $inicio;
                    break;
            }

            $ventas = Venta::whereBetween('created_at', [$inicio, $fin])
                ->selectRaw('SUM(total_pago) as total_ventas, COUNT(*) as cantidad_pedidos')
                ->first();

            return response()->json([
                'tipo' => $tipo,
                'inicio' => $inicio,
                'fin' => $fin,
                'total_ventas' => floatval($ventas->total_ventas),
                'cantidad_pedidos' => intval($ventas->cantidad_pedidos),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error en reporteVentas: ' . $e->getMessage()
            ], 500);
        }
    }



    public function create() {}
    public function store(Request $request) {}
    public function show(string $id) {}
    public function edit(string $id) {}
    public function update(Request $request, string $id) {}
    public function destroy(string $id) {} */
}
