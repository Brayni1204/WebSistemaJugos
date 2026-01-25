@extends('adminlte::page')

@section('title', 'Gastos')

@section('content_header')
    <h1>Lista de Gastos</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <div class="mb-3 d-flex justify-content-between align-items-center">
                <a href="{{ route('admin.gastos.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Registrar Gasto
                </a>
                <span>Total Registros: {{ $gastos->count() }}</span>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Beneficiario</th>
                            <th>Concepto / Detalle</th>
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
                                <td>
                                    @if($gasto->proveedor)
                                        <span class="badge badge-info">Prov</span> {{ $gasto->proveedor->nombre }}
                                    @elseif($gasto->empleado)
                                        <span class="badge badge-warning">Emp</span> {{ $gasto->empleado->name }}
                                    @else
                                        <span class="text-muted">Sin beneficiario</span>
                                    @endif
                                </td>
                                <td>
                                    {{-- Mostrar primer detalle o concepto --}}
                                    @if($gasto->detalles->count() > 0)
                                        {{ $gasto->detalles->first()->producto_nombre }}
                                        @if($gasto->detalles->count() > 1)
                                            <small class="text-muted">(+{{ $gasto->detalles->count() - 1 }} items)</small>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $gasto->comprobante_tipo }} {{ $gasto->comprobante_numero }}</td>
                                <td>S/. {{ number_format($gasto->total, 2) }}</td>
                                <td>{{ optional($gasto->user)->name ?? 'Desconocido' }}</td>
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
