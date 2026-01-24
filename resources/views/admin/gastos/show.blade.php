@extends('adminlte::page')

@section('title', 'Detalle de Gasto')

@section('content_header')
    <h1>Detalle de Gasto #{{ $gasto->id }}</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Información General</h3>
            <div class="card-tools">
                <a href="{{ route('admin.gastos.index') }}" class="btn btn-sm btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <strong>Proveedor:</strong>
                    <p>{{ $gasto->proveedor ? $gasto->proveedor->nombre : 'N/A' }}</p>
                </div>
                <div class="col-md-4">
                    <strong>Fecha:</strong>
                    <p>{{ \Carbon\Carbon::parse($gasto->fecha_gasto)->format('d/m/Y') }}</p>
                </div>
                <div class="col-md-4">
                    <strong>Comprobante:</strong>
                    <p>{{ $gasto->comprobante_tipo }} - {{ $gasto->comprobante_numero }}</p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <strong>Registrado por:</strong>
                    <p>{{ $gasto->user ? $gasto->user->name : 'Sistema' }}</p>
                </div>
                <div class="col-md-4">
                    <strong>Total:</strong>
                    <p class="text-xl font-bold">S/. {{ number_format($gasto->total, 2) }}</p>
                </div>
                <div class="col-md-4">
                    <strong>Observación:</strong>
                    <p>{{ $gasto->observacion ?? 'Sin observación' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Detalles del Gasto</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover text-nowrap">
                <thead>
                    <tr>
                        <th>Producto / Servicio</th>
                        <th>Categoría</th>
                        <th>Cantidad</th>
                        <th>Unidad</th>
                        <th>Precio Unit.</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($gasto->detalles as $detalle)
                        <tr>
                            <td>{{ $detalle->producto_nombre }}</td>
                            <td>{{ $detalle->categoria ? $detalle->categoria->nombre : 'N/A' }}</td>
                            <td>{{ $detalle->cantidad }}</td>
                            <td>{{ $detalle->unidad_medida }}</td>
                            <td>S/. {{ number_format($detalle->precio_unitario, 2) }}</td>
                            <td>S/. {{ number_format($detalle->precio_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="5" class="text-right">Total:</th>
                        <th>S/. {{ number_format($gasto->total, 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@stop
