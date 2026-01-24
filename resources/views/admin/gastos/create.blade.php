@extends('adminlte::page')

@section('title', 'Registrar Gasto')

@section('content_header')
    <h1>Registrar Nuevo Gasto</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.gastos.store') }}" method="POST" id="form-gasto">
                @csrf
                
                {{-- Cabecera del Gasto --}}
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Proveedor</label>
                            <select name="proveedor_id" class="form-control select2">
                                <option value="">Seleccione Proveedor (Opcional)</option>
                                @foreach($proveedores as $proveedor)
                                    <option value="{{ $proveedor->id }}">{{ $proveedor->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Fecha del Gasto</label>
                            <input type="date" name="fecha_gasto" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Categoría General (Opcional)</label>
                            <select class="form-control" id="categoria_global">
                                <option value="">Seleccionar para todos los items</option>
                                @foreach($categorias as $categoria)
                                    <option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Tipo Comprobante</label>
                            <select name="comprobante_tipo" class="form-control">
                                <option value="Boleta">Boleta</option>
                                <option value="Factura">Factura</option>
                                <option value="Ticket">Ticket</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Número Comprobante</label>
                            <input type="text" name="comprobante_numero" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Observación</label>
                            <input type="text" name="observacion" class="form-control">
                        </div>
                    </div>
                </div>

                <hr>

                {{-- Detalle del Gasto --}}
                <h4>Detalle del Gasto</h4>
                <div class="row mb-2">
                    <div class="col-md-3">
                        <input type="text" id="temp_producto" class="form-control" placeholder="Producto/Servicio">
                    </div>
                    <div class="col-md-2">
                        <input type="number" id="temp_cantidad" class="form-control" placeholder="Cant." step="0.01">
                    </div>
                    <div class="col-md-2">
                        <input type="text" id="temp_unidad" class="form-control" placeholder="Unidad (kg, lt, und)">
                    </div>
                    <div class="col-md-2">
                        <input type="number" id="temp_precio" class="form-control" placeholder="Precio Unit." step="0.01">
                    </div>
                    <div class="col-md-2">
                         <select id="temp_categoria" class="form-control">
                            <option value="">Categoría</option>
                            @foreach($categorias as $categoria)
                                <option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-success btn-block" onclick="agregarDetalle()">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="thead-light">
                            <tr>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th>Cantidad</th>
                                <th>Unidad</th>
                                <th>P. Unit.</th>
                                <th>Subtotal</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="tabla_detalles">
                            <!-- Filas agregadas dinámicamente -->
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" class="text-right font-weight-bold">TOTAL:</td>
                                <td colspan="2" class="font-weight-bold">S/. <span id="total_mostrar">0.00</span></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <input type="hidden" name="total" id="input_total">

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary btn-lg" id="btn_guardar" disabled>Guardar Gasto</button>
                    <a href="{{ route('admin.gastos.index') }}" class="btn btn-secondary btn-lg">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@stop

@section('js')
<script>
    let indice = 0;
    let total = 0;

    function agregarDetalle() {
        let producto = $('#temp_producto').val();
        let cantidad = parseFloat($('#temp_cantidad').val());
        let unidad = $('#temp_unidad').val();
        let precio = parseFloat($('#temp_precio').val());
        let categoria_id = $('#temp_categoria').val();
        let categoria_nombre = $('#temp_categoria option:selected').text();
        
        // Si se seleccionó una categoría global y no una específica, usar la global
        let categoria_global_id = $('#categoria_global').val();
        if (!categoria_id && categoria_global_id) {
            categoria_id = categoria_global_id;
            categoria_nombre = $('#categoria_global option:selected').text();
        }

        if (!producto || !cantidad || !precio || !unidad) {
            alert('Por favor complete los campos del producto.');
            return;
        }

        let subtotal = cantidad * precio;
        total += subtotal;

        let fila = `
            <tr id="fila_${indice}">
                <td>
                    <input type="hidden" name="detalles[${indice}][producto_nombre]" value="${producto}">
                    ${producto}
                </td>
                <td>
                    <input type="hidden" name="detalles[${indice}][categoria_id]" value="${categoria_id}">
                    ${categoria_id ? categoria_nombre : '-'}
                </td>
                <td>
                    <input type="hidden" name="detalles[${indice}][cantidad]" value="${cantidad}">
                    ${cantidad}
                </td>
                <td>
                    <input type="hidden" name="detalles[${indice}][unidad_medida]" value="${unidad}">
                    ${unidad}
                </td>
                <td>
                    <input type="hidden" name="detalles[${indice}][precio_unitario]" value="${precio}">
                    ${precio.toFixed(2)}
                </td>
                <td>
                    <input type="hidden" name="detalles[${indice}][precio_total]" value="${subtotal}">
                    ${subtotal.toFixed(2)}
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm" onclick="eliminarDetalle(${indice}, ${subtotal})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;

        $('#tabla_detalles').append(fila);
        actualizarTotal();
        limpiarCampos();
        indice++;
    }

    function eliminarDetalle(index, subtotal) {
        $('#fila_' + index).remove();
        total -= subtotal;
        actualizarTotal();
    }

    function actualizarTotal() {
        $('#total_mostrar').text(total.toFixed(2));
        $('#input_total').val(total.toFixed(2));
        
        if (total > 0) {
            $('#btn_guardar').prop('disabled', false);
        } else {
            $('#btn_guardar').prop('disabled', true);
        }
    }

    function limpiarCampos() {
        $('#temp_producto').val('');
        $('#temp_cantidad').val('');
        $('#temp_precio').val('');
        // No limpiamos unidad por si agregan varios items similares
        $('#temp_producto').focus();
    }
</script>
@stop
