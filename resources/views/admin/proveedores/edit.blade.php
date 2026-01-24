@extends('adminlte::page')

@section('title', 'Editar Proveedor')

@section('content_header')
    <h1>Editar Proveedor</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.proveedores.update', $proveedor) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="nombre_proveedor">Nombre / Razón Social</label>
                            <input type="text" name="nombre_proveedor" id="nombre_proveedor" class="form-control" value="{{ old('nombre_proveedor', $proveedor->nombre_proveedor) }}" required>
                            @error('nombre_proveedor')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="ruc_dni">RUC / DNI</label>
                            <input type="text" name="ruc_dni" id="ruc_dni" class="form-control" value="{{ old('ruc_dni', $proveedor->ruc_dni) }}">
                            @error('ruc_dni')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="telefono">Teléfono</label>
                            <input type="text" name="telefono" id="telefono" class="form-control" value="{{ old('telefono', $proveedor->telefono) }}">
                            @error('telefono')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $proveedor->email) }}">
                            @error('email')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="direccion">Dirección</label>
                    <input type="text" name="direccion" id="direccion" class="form-control" value="{{ old('direccion', $proveedor->direccion) }}">
                    @error('direccion')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="status">Estado</label>
                    <select name="status" id="status" class="form-control">
                        <option value="1" {{ old('status', $proveedor->status) == '1' ? 'selected' : '' }}>Activo</option>
                        <option value="0" {{ old('status', $proveedor->status) == '0' ? 'selected' : '' }}>Inactivo</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Actualizar Proveedor</button>
                <a href="{{ route('admin.proveedores.index') }}" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
@stop
