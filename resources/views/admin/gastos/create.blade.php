@extends('adminlte::page')

@section('title', 'Registrar Gasto')

@section('content_header')
    <h1>Registrar Nuevo Gasto</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <h5><i class="icon fas fa-ban"></i> Error!</h5>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.gastos.store') }}" method="POST" id="form-gasto">
                @csrf
                
                {{-- Cabecera del Gasto --}}
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Tipo de Beneficiario</label>
                            <div class="d-flex">
                                <div class="custom-control custom-radio mr-3">
                                    <input class="custom-control-input" type="radio" id="tipo_proveedor" name="tipo_beneficiario" value="proveedor" checked>
                                    <label for="tipo_proveedor" class="custom-control-label">Proveedor</label>
                                </div>
                                <div class="custom-control custom-radio">
                                    <input class="custom-control-input" type="radio" id="tipo_empleado" name="tipo_beneficiario" value="empleado">
                                    <label for="tipo_empleado" class="custom-control-label">Empleado</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4" id="div_proveedor">
                        <div class="form-group">
                            <label>Proveedor</label>
                            <select name="proveedor_id" class="form-control select2" style="width: 100%;">
                                <option value="">Seleccione Proveedor (Opcional)</option>
                                @foreach($proveedores as $proveedor)
                                    <option value="{{ $proveedor->id }}">{{ $proveedor->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4 d-none" id="div_empleado">
                        <div class="form-group">
                            <label>Empleado</label>
                            <select name="empleado_id" class="form-control select2" style="width: 100%;">
                                <option value="">Seleccione Empleado (Opcional)</option>
                                @foreach($empleados as $empleado)
                                    <option value="{{ $empleado->id }}">{{ $empleado->name }}</option>
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
                </div>

                {{-- Campos exclusivos de Proveedor --}}
                <div id="seccion_proveedor">
                    <div class="row">
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

                    {{-- Detalle del Gasto (Tabla) --}}
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
                </div>

                {{-- Campos exclusivos de Empleado --}}
                <div id="seccion_empleado" class="d-none">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Concepto de Pago / Motivo</label>
                                <input type="text" name="concepto_pago" id="concepto_pago" class="form-control" placeholder="Ej: Adelanto de Sueldo, Bono, Pasajes">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Monto (S/.)</label>
                                <input type="number" name="monto_pago" id="monto_pago" class="form-control" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Observación (Opcional)</label>
                                <input type="text" name="observacion_empleado" class="form-control" placeholder="Detalles adicionales...">
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Al registrar este pago, se generará un comprobante interno tipo "Recibo".
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary btn-lg" id="btn_guardar">Guardar Gasto</button>
                    <a href="{{ route('admin.gastos.index') }}" class="btn btn-secondary btn-lg">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@stop

@section('js')
<script>
    $(document).ready(function() {
        // Inicializar select2
        $('.select2').select2({
            theme: 'bootstrap4',
            width: '100%'
        });

        // Estado inicial
        toggleSecciones($('input[name="tipo_beneficiario"]:checked').val());

        $('input[name="tipo_beneficiario"]').change(function() {
            toggleSecciones(this.value);
        });
    });

    function toggleSecciones(tipo) {
        if (tipo === 'proveedor') {
            $('#div_proveedor').removeClass('d-none');
            $('#div_empleado').addClass('d-none');
            $('#seccion_proveedor').removeClass('d-none');
            $('#seccion_empleado').addClass('d-none');
            
            // Limpiar selección de empleado
            $('select[name="empleado_id"]').val('').trigger('change');
            
            // Validar botón guardar (depende de la tabla)
            actualizarTotal();
        } else {
            $('#div_proveedor').addClass('d-none');
            $('#div_empleado').removeClass('d-none');
            $('#seccion_proveedor').addClass('d-none');
            $('#seccion_empleado').removeClass('d-none');
            
            // Limpiar selección de proveedor
            $('select[name="proveedor_id"]').val('').trigger('change');
            
            // Habilitar botón guardar siempre (validación HTML5 se encargará)
            $('#btn_guardar').prop('disabled', false);
        }
    }

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
