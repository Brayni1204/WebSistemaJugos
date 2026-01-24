@extends('adminlte::page')

@section('title', 'Gastos')

@section('content_header')
    <h1>Lista de Gastos</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="mb-3">
                <a href="{{ route('admin.gastos.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Registrar Gasto
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Proveedor</th>
                            <th>Comprobante</th>
                            <th>Total</th>
                            <th>Registrado por</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($gastos as $gasto)
                            <tr>
                                <td>{{ $gasto->id }}</td>
                                <td>{{ \Carbon\Carbon::parse($gasto->fecha_gasto)->format('d/m/Y') }}</td>
                                <td>{{ $gasto->proveedor ? $gasto->proveedor->nombre : 'Sin proveedor' }}</td>
                                <td>{{ $gasto->comprobante_tipo }} {{ $gasto->comprobante_numero }}</td>
                                <td>S/. {{ number_format($gasto->total, 2) }}</td>
                                <td>{{ $gasto->user->name }}</td>
                                <td>
                                    <a href="{{ route('admin.gastos.show', $gasto) }}" class="btn btn-sm btn-info" title="Ver Detalles"><i class="fas fa-eye"></i></a>
                                    <form action="{{ route('admin.gastos.destroy', $gasto) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Está seguro de eliminar este gasto?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Eliminar"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@stop
