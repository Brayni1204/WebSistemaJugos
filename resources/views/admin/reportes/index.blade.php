@extends('adminlte::page')

@section('title', 'Reporte de Ventas')

@section('content_header')
    <h1 class="text-center">📊 Reporte de Ventas</h1>
@stop

@section('content')
    <div class="container mt-4">
        <h2 class="mb-4">📊 Generar Reportes de Ventas</h2>

        <!-- 🔹 Selección del tipo de reporte -->
        <div class="mb-3">
            <label for="tipoReporte" class="form-label"><strong>Seleccionar tipo de reporte:</strong></label>
            <select id="tipoReporte" class="form-select">
                <option value="diario">📅 Diario</option>
                <option value="semanal">📆 Semanal</option>
                <option value="mensual">📊 Mensual</option>
                <option value="rango">📌 Rango de Fechas</option>
            </select>
        </div>

        <!-- 🔹 Filtros dinámicos según el tipo de reporte -->
        <div id="filtros">
            <!-- Diario -->
            <div id="filtroDiario" class="mb-3">
                <label for="fechaDiaria" class="form-label">Selecciona una fecha:</label>
                <input type="date" id="fechaDiaria" class="form-control">
            </div>

            <!-- Semanal -->
            <div id="filtroSemanal" class="mb-3 d-none">
                <label for="inicioSemana" class="form-label">Selecciona el inicio de la semana:</label>
                <input type="date" id="inicioSemana" class="form-control">
            </div>

            <!-- Mensual -->
            <div id="filtroMensual" class="mb-3 d-none">
                <label for="mes" class="form-label">Selecciona un mes:</label>
                <input type="month" id="mes" class="form-control">
            </div>

            <!-- Rango de Fechas -->
            <div id="filtroRango" class="row d-none">
                <div class="col-md-6">
                    <label for="fechaInicio" class="form-label">Fecha Inicio:</label>
                    <input type="date" id="fechaInicio" class="form-control">
                </div>
                <div class="col-md-6">
                    <label for="fechaFin" class="form-label">Fecha Fin:</label>
                    <input type="date" id="fechaFin" class="form-control">
                </div>
            </div>
        </div>

        <!-- 🔹 Botón para generar reporte -->
        <div class="mt-3">
            <button id="btnGenerar" class="btn btn-primary">🔍 Generar Reporte</button>
        </div>

        <!-- 🔹 Resultados del reporte -->
        <div id="resultado" class="mt-5 d-none">
            <h4 class="mb-3">📌 Resultados del Reporte</h4>
            <ul class="list-group">
                <li class="list-group-item"><strong>Total de Ventas:</strong> <span id="totalVentas"></span></li>
                <li class="list-group-item"><strong>Producto más Vendido:</strong> <span id="productoMasVendido"></span>
                </li>
                <li class="list-group-item"><strong>Clientes Frecuentes:</strong>
                    <ul id="clientesFrecuentes" class="mt-2"></ul>
                </li>
            </ul>
        </div>
    </div>
@stop

@section('js')
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const tipoReporte = document.getElementById("tipoReporte");
            const filtros = {
                diario: document.getElementById("filtroDiario"),
                semanal: document.getElementById("filtroSemanal"),
                mensual: document.getElementById("filtroMensual"),
                rango: document.getElementById("filtroRango"),
            };

            // 🔹 Ocultar y mostrar filtros dinámicamente
            tipoReporte.addEventListener("change", function() {
                Object.values(filtros).forEach(filtro => filtro.classList.add("d-none"));
                filtros[this.value].classList.remove("d-none");
            });

            document.getElementById("btnGenerar").addEventListener("click", function() {
                const tipo = tipoReporte.value;
                let url = `/admin/reportes/${tipo}`;
                let params = {};

                if (tipo === "diario") {
                    params.fecha = document.getElementById("fechaDiaria").value;
                } else if (tipo === "semanal") {
                    params.inicio_semana = document.getElementById("inicioSemana").value;
                } else if (tipo === "mensual") {
                    const mesAnio = document.getElementById("mes").value.split("-");
                    params.mes = mesAnio[1];
                    params.anio = mesAnio[0];
                } else if (tipo === "rango") {
                    params.fecha_inicio = document.getElementById("fechaInicio").value;
                    params.fecha_fin = document.getElementById("fechaFin").value;
                }

                fetch(url + "?" + new URLSearchParams(params))
                    .then(response => response.json())
                    .then(data => mostrarResultados(data))
                    .catch(error => console.error("Error en el reporte:", error));
            });

            function mostrarResultados(data) {
                document.getElementById("resultado").classList.remove("d-none");

                document.getElementById("totalVentas").textContent =
                    `$${parseFloat(data.total_ventas || 0).toFixed(2)}`;
                document.getElementById("productoMasVendido").textContent = data.producto_mas_vendido || 'N/A';

                let clientesHTML = "";
                if (Array.isArray(data.clientes_frecuentes)) {
                    data.clientes_frecuentes.forEach(cliente => {
                        clientesHTML += `<li>${cliente.nombre} - ${cliente.total_compras} compras</li>`;
                    });
                }
                document.getElementById("clientesFrecuentes").innerHTML = clientesHTML ||
                    "<li>No hay clientes frecuentes</li>";
            }


        });
    </script>
@stop
