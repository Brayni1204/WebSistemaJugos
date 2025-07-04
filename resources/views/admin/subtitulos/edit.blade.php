@extends('adminlte::page')

@section('title', 'Editar Subtítulo')

@section('content_header')

@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="container-fluid">
                <div class="form-container">
                    <h2 class="text-center mb-4">Modificar Subtítulo</h2>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form id="subtituloForm" action="{{ route('admin.subtitulos.update', $subtitulo) }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label>Título del Subtítulo</label>
                            <input type="text" name="titulo_subtitulo" class="form-control"
                                value="{{ old('titulo_subtitulo', $subtitulo->titulo_subtitulo) }}" required>
                        </div>

                        <div class="form-group">
                            <label>Resumen</label>
                            <textarea name="resumen" style="height: 280px" class="form-control">{{ old('resumen', $subtitulo->resumen) }}</textarea>
                        </div>

                        <div class="form-group">
                            <label>Estado</label>
                            <select name="status" class="form-control">
                                <option value="1" {{ $subtitulo->status == 1 ? 'selected' : '' }}>Borrador</option>
                                <option value="2" {{ $subtitulo->status == 2 ? 'selected' : '' }}>Publicado</option>
                            </select>
                        </div>

                        <!-- Imagen Actual -->
                        <div class="form-group">
                            <label>Imagen Actual:</label><br>
                            <div class="image-container">
                                @if ($subtitulo->image)
                                    <img id="preview_actual" src="{{ Storage::url($subtitulo->image->url) }}"
                                        class="image-preview">
                                @else
                                    <p><small>Sin imagen</small></p>
                                @endif
                            </div>
                        </div>

                        <!-- Selector de nueva imagen -->
                        <div class="form-group">
                            <label>Subir Nueva Imagen</label>
                            <div class="custom-file" id="imageUpload">
                                <span class="custom-file-label">Haz clic para seleccionar una imagen</span>
                                <input type="file" name="imagen" id="imagen" accept="image/*">
                            </div>
                            <div style="display: flex; justify-content: center">
                                <img id="preview" class="image-preview">
                            </div>
                        </div>

                        <div class="d-flex" style="gap: 10px">
                            <button type="submit" class="btn btn-primary btn-custom"><i class="fas fa-save"></i>
                                Actualizar</button>
                            <a href="{{ route('admin.paginas.show', $subtitulo->paginas) }}" class="btn btn-custom">
                                <i class="fas fa-times"></i> 🔙 Cancelar
                            </a>
                        </div>
                        <div class="floating-btn-container">
                            <!-- 🔹 Botón para Agregar Subtítulo -->
                            <button type="submit" title="Actualizar Subtitulo" class="btn btn-primary btn-custom"><i
                                    class="fas fa-edit"></i></button>
                            <!-- 🔙 Botón para Regresar -->
                            <a href="{{ route('admin.paginas.show', $subtitulo->paginas) }}" class="floating-btn back-btn"
                                title="Regresar">
                                <i class="fas fa-arrow-left"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>

    <script>
        // Manejar la previsualización de la nueva imagen seleccionada
        document.getElementById('imagen').addEventListener('change', function(event) {
            let file = event.target.files[0];
            let preview = document.getElementById('preview');
            let label = document.querySelector('.custom-file-label');

            if (file) {
                let reader = new FileReader();
                reader.onload = function() {
                    preview.src = reader.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
                label.textContent = file.name;
            } else {
                preview.style.display = 'none';
                label.textContent = "Haz clic para seleccionar una imagen";
            }
        });

        // Mejor experiencia de usuario en el input file
        document.getElementById('imageUpload').addEventListener('click', function() {
            document.getElementById('imagen').click();
        });

        // Confirmación con SweetAlert2 antes de enviar el formulario
        document.getElementById('subtituloForm').addEventListener('submit', function(event) {
            event.preventDefault();

            Swal.fire({
                title: "¿Guardar cambios?",
                text: "Se actualizará la información del subtítulo.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Sí, guardar"
            }).then((result) => {
                if (result.isConfirmed) {
                    event.target.submit();
                }
            });
        });
    </script>
@stop

@section('css')
    <style>
        /* 🔹 HEADER CON DEGRADADO 🔹 */
        .title-header {
            font-size: 1.8rem;
            font-weight: bold;
            text-align: center;
            padding: 15px;
            border-radius: 8px;
            color: #fff;
            background: linear-gradient(90deg, #2c3e50, #34495e);
        }

        .form-container {
            max-width: 700px;
            margin: auto;
            padding: 20px;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }

        .form-group label {
            font-weight: bold;
            color: #333;
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid #ddd;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0px 0px 5px rgba(0, 123, 255, 0.5);
        }

        /* 🖼️ INPUT DE IMAGEN MEJORADO */
        .custom-file {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 10px;
            border: 2px dashed #ccc;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .custom-file:hover {
            background: #eef3f8;
            border-color: #007bff;
        }

        .custom-file input {
            display: none;
        }

        .custom-file-label {
            font-size: 16px;
            font-weight: 500;
            color: #555;
        }

        .image-preview {
            display: none;
            width: 100%;
            max-width: 250px;
            border-radius: 10px;
            margin-top: 15px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
        }

        .btn-custom {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border-radius: 8px;
        }

        @media (max-width: 768px) {
            .form-container {
                padding: 15px;
            }

            .btn-custom {
                width: 100%;
            }
        }
    </style>
    <style>
        /* 🎨 Contenedor de Botones */
        .floating-btn-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            display: grid;
            gap: 12px;
            align-items: center;
        }

        /* 🎨 Estilo General de Botones Flotantes */
        .floating-btn {
            background-color: #007bff;
            color: white;
            width: 55px;
            height: 55px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 22px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.3);
            transition: background 0.3s, transform 0.3s, box-shadow 0.3s;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }

        .floating-btn:hover {
            background-color: #0056b3;
            transform: scale(1.1);
            box-shadow: 0px 6px 15px rgba(0, 0, 0, 0.4);
        }

        /* 🟥 Botón de Regresar */
        .back-btn {
            background-color: #dc3545;
        }

        .back-btn:hover {
            background-color: #b02a37;
        }

        /* 🟩 Botón para Publicar/Borrador */
        .status-btn {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 0;
        }

        .draft-btn {
            background-color: #f39c12;
        }

        .draft-btn:hover {
            background-color: #d68910;
        }

        .publish-btn {
            background-color: #28a745;
        }

        .publish-btn:hover {
            background-color: #218838;
        }

        /* 👁️ Botón de Ver */
        .view-btn {
            background-color: #17a2b8;
        }

        .view-btn:hover {
            background-color: #138496;
        }

        /* 🎯 Estilo para el Formulario Flotante */
        .floating-btn-form {
            display: inline-block;
        }
    </style>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.getElementById('subtituloForm').addEventListener('submit', function(event) {
            event.preventDefault();

            Swal.fire({
                title: "¿Guardar cambios?",
                text: "Se actualizará la información del subtítulo.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Sí, guardar"
            }).then((result) => {
                if (result.isConfirmed) {
                    event.target.submit();
                }
            });
        });
    </script>
@stop
