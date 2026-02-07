<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\DetalleVenta;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Venta;
use App\Models\Gasto;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel; // Added for Excel exports
use Barryvdh\DomPDF\Facade\Pdf; // Added for PDF exports

class CajaController extends Controller
{
    public function index()
    {
        $categorias = Categoria::all();
        return view('admin.reportes.index', compact('categorias'));
    }

    private function obtenerEstadisticas($ventas)
    {
        // Obtener el producto más vendido
        $productoMasVendido = 'N/A';
        if ($ventas->isNotEmpty()) {
            $productoMasVendido = DB::table('productos')
                ->select('productos.nombre_producto')
                ->join('detalle_ventas', 'productos.id', '=', 'detalle_ventas.producto_id')
                ->whereIn('detalle_ventas.venta_id', $ventas->pluck('id'))
                ->groupBy('productos.id', 'productos.nombre_producto')
                ->orderByRaw('SUM(detalle_ventas.cantidad) DESC')
                ->limit(1)
                ->pluck('productos.nombre_producto')
                ->first() ?? 'N/A';
        }

        // Obtener clientes frecuentes
        $clientesFrecuentes = collect();
        if ($ventas->isNotEmpty()) {
            $clientesFrecuentes = DB::table('clientes')
                ->select('clientes.nombre', DB::raw('COUNT(*) as total_compras'))
                ->join('ventas', 'clientes.id', '=', 'ventas.cliente_id')
                ->whereIn('ventas.id', $ventas->pluck('id'))
                ->groupBy('clientes.id', 'clientes.nombre')
                ->orderBy('total_compras', 'DESC')
                ->limit(5)
                ->get();
        }


        return [
            'producto_mas_vendido' => $productoMasVendido,
            'clientes_frecuentes' => $clientesFrecuentes
        ];
    }


    // 🔹 Reporte diario
    public function reporteDiario(Request $request)
    {
        try {
            $fechaInput = $request->input('fecha');

            // Verifica que la fecha esté presente
            if (!$fechaInput) {
                return response()->json(['error' => 'Fecha requerida'], 400);
            }

            // 🚀 CORRECCIÓN: Convertir rango horario a UTC para coincidir con la BD
            $fechaInicio = Carbon::parse($fechaInput)->startOfDay()->setTimezone('UTC');
            $fechaFin = Carbon::parse($fechaInput)->endOfDay()->setTimezone('UTC');

            $ventasDelDia = Venta::whereBetween('created_at', [$fechaInicio, $fechaFin])->get();
            $totalVentas = $ventasDelDia->sum('total_pago');
            $cantidadPedidos = $ventasDelDia->count();

            // Nota: Para gastos asumimos la misma lógica si usan timestamp, si es date puro no afecta
            $totalGastos = Gasto::whereBetween('fecha_gasto', [$fechaInicio, $fechaFin])->sum('total');
            $gananciaNeta = $totalVentas - $totalGastos;

            $estadisticas = $this->obtenerEstadisticas($ventasDelDia);


            return response()->json([
                'fecha' => $fechaInput,
                'total_ventas' => $totalVentas,
                'total_gastos' => $totalGastos,
                'ganancia_neta' => $gananciaNeta,
                'cantidad_pedidos' => $cantidadPedidos,
                'producto_mas_vendido' => $estadisticas['producto_mas_vendido'],
                'clientes_frecuentes' => $estadisticas['clientes_frecuentes']
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al generar el reporte diario', 'message' => $e->getMessage()], 500);
        }
    }


    // 🔹 Reporte semanal
    public function reporteSemanal(Request $request)
    {
        try {
            if (!$request->has('inicio_semana')) {
                return response()->json(['error' => 'Fecha de inicio de semana requerida'], 400);
            }

            // 🚀 CORRECCIÓN: Convertir rango horario a UTC
            $inicioSemana = Carbon::parse($request->input('inicio_semana'))->startOfWeek()->setTimezone('UTC');
            $finSemana = Carbon::parse($request->input('inicio_semana'))->endOfWeek()->setTimezone('UTC');

            $ventas = Venta::whereBetween('created_at', [$inicioSemana, $finSemana])->with('detalles')->get();
            $totalVentas = $ventas->sum('total_pago');

            $totalGastos = Gasto::whereBetween('fecha_gasto', [$inicioSemana, $finSemana])->sum('total');
            $gananciaNeta = $totalVentas - $totalGastos;

            $estadisticas = $this->obtenerEstadisticas($ventas);

            return response()->json([
                'rango' => $inicioSemana->toDateString() . ' -> ' . $finSemana->toDateString(),
                'total_ventas' => $totalVentas,
                'total_gastos' => $totalGastos,
                'ganancia_neta' => $gananciaNeta,
                'producto_mas_vendido' => $estadisticas['producto_mas_vendido'],
                'clientes_frecuentes' => $estadisticas['clientes_frecuentes']
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al generar el reporte semanal', 'message' => $e->getMessage()], 500);
        }
    }


    // 🔹 Reporte mensual
    public function reporteMensual(Request $request)
    {
        try {
            $mes = $request->input('mes');
            $anio = $request->input('anio', Carbon::now()->year); // Año actual por defecto

            if (!$mes) {
                return response()->json(['error' => 'Mes requerido'], 400);
            }

            // 🚀 CORRECCIÓN: Usar rangos UTC en lugar de whereMonth/whereYear directo
            $fechaInicio = Carbon::create($anio, $mes, 1)->startOfMonth()->setTimezone('UTC');
            $fechaFin = Carbon::create($anio, $mes, 1)->endOfMonth()->setTimezone('UTC');

            $ventas = Venta::whereBetween('created_at', [$fechaInicio, $fechaFin])
                ->with('detalles')
                ->get();
            $totalVentas = $ventas->sum('total_pago');

            $totalGastos = Gasto::whereBetween('fecha_gasto', [$fechaInicio, $fechaFin])
                ->sum('total');
            $gananciaNeta = $totalVentas - $totalGastos;

            $estadisticas = $this->obtenerEstadisticas($ventas);

            return response()->json([
                'mes' => $mes,
                'anio' => $anio,
                'total_ventas' => $totalVentas,
                'total_gastos' => $totalGastos,
                'ganancia_neta' => $gananciaNeta,
                'producto_mas_vendido' => $estadisticas['producto_mas_vendido'],
                'clientes_frecuentes' => $estadisticas['clientes_frecuentes']
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al generar el reporte mensual', 'message' => $e->getMessage()], 500);
        }
    }

    // 🔹 Reporte por rango de fechas
    public function reportePorRango(Request $request)
    {
        try {
            $fechaInicioInput = $request->input('fecha_inicio');
            $fechaFinInput = $request->input('fecha_fin');

            if (!$fechaInicioInput || !$fechaFinInput) {
                return response()->json(['error' => 'Fechas de inicio y fin requeridas'], 400);
            }

            // 🚀 CORRECCIÓN: Convertir rango horario a UTC
            $fechaInicio = Carbon::parse($fechaInicioInput)->startOfDay()->setTimezone('UTC');
            $fechaFin = Carbon::parse($fechaFinInput)->endOfDay()->setTimezone('UTC');

            $ventas = Venta::whereBetween('created_at', [$fechaInicio, $fechaFin])->with('detalles')->get();
            $totalVentas = $ventas->sum('total_pago');

            // Calcular Gastos y Ganancia
            $totalGastos = Gasto::whereBetween('fecha_gasto', [$fechaInicio, $fechaFin])->sum('total');
            $gananciaNeta = $totalVentas - $totalGastos;

            $estadisticas = $this->obtenerEstadisticas($ventas);

            return response()->json([
                'rango' => $fechaInicio->toDateString() . ' -> ' . $fechaFin->toDateString(),
                'total_ventas' => $totalVentas,
                'total_gastos' => $totalGastos,
                'ganancia_neta' => $gananciaNeta,
                'producto_mas_vendido' => $estadisticas['producto_mas_vendido'],
                'clientes_frecuentes' => $estadisticas['clientes_frecuentes']
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al generar el reporte por rango de fechas', 'message' => $e->getMessage()], 500);
        }
    }

    // 🔹 Nuevo: Ventas por día (detallado)
    public function ventasPorDia(Request $request)
    {
        try {
            $fechaInput = $request->input('fecha');
            $fecha = $fechaInput ? Carbon::parse($fechaInput) : Carbon::now();
            
            // 🚀 CORRECCIÓN: Usar whereBetween con UTC explícito para evitar problemas de zona horaria
            $inicio = $fecha->copy()->startOfDay()->setTimezone('UTC');
            $fin = $fecha->copy()->endOfDay()->setTimezone('UTC');

            $ventas = Venta::whereBetween('created_at', [$inicio, $fin])
                ->selectRaw('COALESCE(SUM(total_pago), 0) as total_ventas, COUNT(*) as cantidad_pedidos')
                ->first();

            return response()->json([
                'fecha' => $fecha->toDateString(),
                'total_ventas' => floatval($ventas->total_ventas),
                'cantidad_pedidos' => intval($ventas->cantidad_pedidos),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error en ventasPorDia: ' . $e->getMessage()
            ], 500);
        }
    }

    // 🔹 Nuevo: Frecuencia de Clientes
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

    // 🔹 Nuevo: Productos Más Vendidos
    public function productosMasVendidos(Request $request)
    {
        try {
            $fechaInicioInput = $request->input('fecha_inicio', Carbon::now()->startOfMonth()->toDateString());
            $fechaFinInput = $request->input('fecha_fin', Carbon::now()->toDateString());

            $fechaInicio = Carbon::parse($fechaInicioInput)->startOfDay()->setTimezone('UTC');
            $fechaFin = Carbon::parse($fechaFinInput)->endOfDay()->setTimezone('UTC');

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

    // 🔹 Nuevo: Reporte de Ventas General
    public function reporteVentas(Request $request)
    {
        try {
            $tipo = $request->input('tipo', 'diario'); // 'diario', 'semanal', 'mensual'
            $fecha = Carbon::now(); // Esto usa APP_TIMEZONE (Lima)

            switch ($tipo) {
                case 'semanal':
                    $inicio = $fecha->copy()->startOfWeek()->startOfDay()->setTimezone('UTC');
                    $fin = $fecha->copy()->endOfWeek()->endOfDay()->setTimezone('UTC');
                    break;
                case 'mensual':
                    $inicio = $fecha->copy()->startOfMonth()->startOfDay()->setTimezone('UTC');
                    $fin = $fecha->copy()->endOfMonth()->endOfDay()->setTimezone('UTC');
                    break;
                default: // diario
                    $inicio = $fecha->copy()->startOfDay()->setTimezone('UTC');
                    $fin = $fecha->copy()->endOfDay()->setTimezone('UTC');
                    break;
            }

            $ventas = Venta::whereBetween('created_at', [$inicio, $fin])
                ->selectRaw('SUM(total_pago) as total_ventas, COUNT(*) as cantidad_pedidos')
                ->first();

            return response()->json([
                'tipo' => $tipo,
                'inicio' => $inicio->toDateTimeString(), // UTC
                'fin' => $fin->toDateTimeString(),       // UTC
                'total_ventas' => floatval($ventas->total_ventas),
                'cantidad_pedidos' => intval($ventas->cantidad_pedidos),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error en reporteVentas: ' . $e->getMessage()
            ], 500);
        }
    }


    private function filtrarVentas(Request $request)
    {
        $query = Venta::query();

        // Filtro de fecha
        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            // 🚀 CORRECCIÓN: Convertir rango horario a UTC
            $fechaInicio = Carbon::parse($request->input('fecha_inicio'))->startOfDay()->setTimezone('UTC');
            $fechaFin = Carbon::parse($request->input('fecha_fin'))->endOfDay()->setTimezone('UTC');
            $query->whereBetween('created_at', [$fechaInicio, $fechaFin]);
        }

        // Filtro de categoría
        if ($request->filled('categoria_id')) {
            $query->whereHas('detalles', function ($q) use ($request) {
                $q->whereHas('producto', function ($q2) use ($request) {
                    $q2->where('id_categoria', $request->input('categoria_id'));
                });
            });
        }
        
        return $query;
    }

    // 🔹 Nuevo: Reporte General para el Dashboard
    public function reporteGeneral(Request $request)
    {
        try {
            // Validar y parsear fechas
            $request->validate([
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
                'categoria_id' => 'nullable|integer|exists:categorias,id'
            ]);

            // Obtener la consulta base de ventas filtradas
            $ventasQuery = $this->filtrarVentas($request);

            // Clonar la consulta para obtener los resultados
            $ventas = $ventasQuery->clone()->with('pedido')->get();
            $ventaIds = $ventas->pluck('id');

            // Calcular estadísticas de resumen
            $totalVentas = $ventas->sum('total_pago');
            $cantidadPedidos = $ventas->count();

            // Calcular Gastos y Ganancia
            $fechaInicio = Carbon::parse($request->input('fecha_inicio'))->startOfDay()->setTimezone('UTC');
            $fechaFin = Carbon::parse($request->input('fecha_fin'))->endOfDay()->setTimezone('UTC');
            
            $totalGastos = Gasto::whereBetween('fecha_gasto', [$fechaInicio, $fechaFin])->sum('total');
            $gananciaNeta = $totalVentas - $totalGastos;

            // Obtener productos más vendidos (sin límite)
            $productosMasVendidos = DB::table('detalle_ventas')
                ->join('productos', 'detalle_ventas.producto_id', '=', 'productos.id')
                ->whereIn('detalle_ventas.venta_id', $ventaIds)
                ->select('productos.nombre_producto', DB::raw('SUM(detalle_ventas.cantidad) as total_vendido'))
                ->groupBy('productos.nombre_producto')
                ->orderByDesc('total_vendido')
                ->get();
            
            $productoEstrella = $productosMasVendidos->first()->nombre_producto ?? 'N/A';

            // Obtener clientes frecuentes (sin límite)
            $clientesFrecuentes = DB::table('ventas')
                ->join('clientes', 'ventas.cliente_id', '=', 'clientes.id')
                ->whereIn('ventas.id', $ventaIds)
                ->select('clientes.nombre', 'clientes.apellidos', 'clientes.email', DB::raw('COUNT(ventas.id) as compras_realizadas'), DB::raw('SUM(ventas.total_pago) as total_gastado'))
                ->groupBy('clientes.id', 'clientes.nombre', 'clientes.apellidos', 'clientes.email')
                ->orderByDesc('compras_realizadas')
                ->get();

            // 🚀 CORRECCIÓN: Calcular ventas por método de pago usando la colección de ventas
            // Esto asegura que la suma coincida EXACTAMENTE con $totalVentas, incluyendo nulos.
            $ventasPorMetodo = $ventas->groupBy(function ($venta) {
                if (!$venta->pedido) return 'Venta Directa';
                return $venta->pedido->metodo_pago ?: 'No Especificado';
            })->map(function ($grupo, $metodo) {
                return [
                    'metodo_pago' => ucfirst($metodo),
                    'total' => $grupo->sum('total_pago'),
                    'count' => $grupo->count()
                ];
            })->values();

            // Devolver JSON estructurado
            return response()->json([
                'summary' => [
                    'total_ventas' => $totalVentas,
                    'total_gastos' => $totalGastos,
                    'ganancia_neta' => $gananciaNeta,
                    'cantidad_pedidos' => $cantidadPedidos,
                    'producto_estrella' => $productoEstrella,
                ],
                'productos_mas_vendidos' => $productosMasVendidos,
                'clientes_frecuentes' => $clientesFrecuentes,
                'ventas_por_metodo_pago' => $ventasPorMetodo,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Datos de entrada inválidos.', 'messages' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al generar el reporte general', 'message' => $e->getMessage()], 500);
        }
    }


    // 🔹 Exportar Productos Más Vendidos
    public function exportProductosMasVendidos(Request $request, $format)
    {
        $fechaInicio = Carbon::parse($request->input('fecha_inicio'))->startOfDay()->setTimezone('UTC');
        $fechaFin = Carbon::parse($request->input('fecha_fin'))->endOfDay()->setTimezone('UTC');

        $ventas = Venta::whereBetween('created_at', [$fechaInicio, $fechaFin])->get();
        $ventaIds = $ventas->pluck('id');

        $productos = DB::table('detalle_ventas')
            ->join('productos', 'detalle_ventas.producto_id', '=', 'productos.id')
            ->whereIn('detalle_ventas.venta_id', $ventaIds)
            ->select('productos.nombre_producto', DB::raw('SUM(detalle_ventas.cantidad) as total_vendido'))
            ->groupBy('productos.nombre_producto')
            ->orderByDesc('total_vendido')
            ->get();

        $data = [
            'productos' => $productos,
            'fechaInicio' => $fechaInicio->toDateString(),
            'fechaFin' => $fechaFin->toDateString(),
        ];

        if ($format == 'xlsx') {
            // Implement Excel export using Maatwebsite\Excel
            // For simplicity, I'll return a basic array for now.
            // A full implementation would use a dedicated Export class.
            $exportData = $productos->map(function($item) {
                return (array) $item;
            })->toArray();
            array_unshift($exportData, ['Producto', 'Total Vendido']); // Add header
            return Excel::download(new \App\Exports\GenericExport($exportData), 'productos_mas_vendidos_' . $fechaInicio->toDateString() . '_' . $fechaFin->toDateString() . '.xlsx');
        } elseif ($format == 'pdf') {
            $pdf = PDF::loadView('admin.reportes.exports.productos_pdf', $data);
            return $pdf->download('productos_mas_vendidos_' . $fechaInicio->toDateString() . '_' . $fechaFin->toDateString() . '.pdf');
        }

        return back()->with('error', 'Formato de exportación no soportado.');
    }

    // 🔹 Exportar Clientes Frecuentes
    public function exportClientesFrecuentes(Request $request, $format)
    {
        $fechaInicio = Carbon::parse($request->input('fecha_inicio'))->startOfDay()->setTimezone('UTC');
        $fechaFin = Carbon::parse($request->input('fecha_fin'))->endOfDay()->setTimezone('UTC');

        $ventas = Venta::whereBetween('created_at', [$fechaInicio, $fechaFin])->get();
        $ventaIds = $ventas->pluck('id');

        $clientes = DB::table('ventas')
            ->join('clientes', 'ventas.cliente_id', '=', 'clientes.id')
            ->whereIn('ventas.id', $ventaIds)
            ->select('clientes.nombre', DB::raw('COUNT(ventas.id) as compras_realizadas'), DB::raw('SUM(ventas.total_pago) as total_gastado'))
            ->groupBy('clientes.id', 'clientes.nombre', 'clientes.apellidos', 'clientes.email')
            ->orderByDesc('compras_realizadas')
            ->get();

        $data = [
            'clientes' => $clientes,
            'fechaInicio' => $fechaInicio->toDateString(),
            'fechaFin' => $fechaFin->toDateString(),
        ];

        if ($format == 'xlsx') {
            $exportData = $clientes->map(function($item) {
                return (array) $item;
            })->toArray();
            array_unshift($exportData, ['Nombre', 'Compras Realizadas', 'Total Gastado']);
            return Excel::download(new \App\Exports\GenericExport($exportData), 'clientes_frecuentes_' . $fechaInicio->toDateString() . '_' . $fechaFin->toDateString() . '.xlsx');
        } elseif ($format == 'pdf') {
            $pdf = PDF::loadView('admin.reportes.exports.clientes_pdf', $data);
            return $pdf->download('clientes_frecuentes_' . $fechaInicio->toDateString() . '_' . $fechaFin->toDateString() . '.pdf');
        }

        return back()->with('error', 'Formato de exportación no soportado.');
    }

    // 🔹 Exportar Ventas por Método de Pago
    public function exportVentasPorMetodo(Request $request, $format)
    {
        $fechaInicio = Carbon::parse($request->input('fecha_inicio'))->startOfDay()->setTimezone('UTC');
        $fechaFin = Carbon::parse($request->input('fecha_fin'))->endOfDay()->setTimezone('UTC');

        // 🚀 CORRECCIÓN: Usar la misma lógica que el dashboard (agrupación en memoria)
        $ventas = Venta::whereBetween('created_at', [$fechaInicio, $fechaFin])->with('pedido')->get();

        $ventasPorMetodo = $ventas->groupBy(function ($venta) {
            if (!$venta->pedido) return 'Venta Directa';
            return $venta->pedido->metodo_pago ?: 'No Especificado';
        })->map(function ($grupo, $metodo) {
            return (object) [
                'metodo_pago' => ucfirst($metodo),
                'total' => $grupo->sum('total_pago'),
                'count' => $grupo->count()
            ];
        })->values();

        $data = [
            'metodos' => $ventasPorMetodo,
            'fechaInicio' => $fechaInicio->toDateString(),
            'fechaFin' => $fechaFin->toDateString(),
        ];

        if ($format == 'xlsx') {
            $exportData = $ventasPorMetodo->map(function($item) {
                return (array) $item;
            })->toArray();
            array_unshift($exportData, ['Método de Pago', 'Total', 'Cantidad']);
            return Excel::download(new \App\Exports\GenericExport($exportData), 'ventas_por_metodo_' . $fechaInicio->toDateString() . '_' . $fechaFin->toDateString() . '.xlsx');
        } elseif ($format == 'pdf') {
            $pdf = PDF::loadView('admin.reportes.exports.metodos_pago_pdf', $data);
            return $pdf->download('ventas_por_metodo_' . $fechaInicio->toDateString() . '_' . $fechaFin->toDateString() . '.pdf');
        }

        return back()->with('error', 'Formato de exportación no soportado.');
    }


    public function create() {}
    public function store(Request $request) {}
    public function show(string $id) {}
    public function edit(string $id) {}
    public function update(Request $request, string $id) {}
    public function destroy(string $id) {}
}
