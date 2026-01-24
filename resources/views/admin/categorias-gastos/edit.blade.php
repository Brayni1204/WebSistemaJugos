@extends('adminlte::page')

@section('title', 'Editar Categoría de Gasto')

@section('content_header')
    <h1>Editar Categoría de Gasto</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.categorias-gastos.update', $categoriaGasto) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label for="nombre">Nombre</label>
                    <input type="text" name="nombre" id="nombre" class="form-control" value="{{ old('nombre', $categoriaGasto->nombre) }}" required>
                    @error('nombre')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea name="descripcion" id="descripcion" class="form-control" rows="3">{{ old('descripcion', $categoriaGasto->descripcion) }}</textarea>
                    @error('descripcion')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="status">Estado</label>
                    <select name="status" id="status" class="form-control">
                        <option value="1" {{ old('status', $categoriaGasto->status) == '1' ? 'selected' : '' }}>Activo</option>
                        <option value="0" {{ old('status', $categoriaGasto->status) == '0' ? 'selected' : '' }}>Inactivo</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Actualizar Categoría</button>
                <a href="{{ route('admin.categorias-gastos.index') }}" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
@stop
