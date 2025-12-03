@extends('layouts.base')

@section('contenido')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Inventario y Asignaciones</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('registro_equipo.create') }}" class="btn btn-sm btn-primary me-2">
            <i class="fas fa-plus me-1"></i>Agregar nuevo
        </a>
        <button class="btn btn-sm btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#modalReportes">
            <i class="fas fa-file-export me-1"></i>Generar Reporte
        </button>
        <button class="btn btn-sm btn-outline-dark" type="button" onclick="abrirScannerQR()">
            <i class="fas fa-qrcode me-1"></i>Escanear QR
        </button>
    </div>
</div>

<!-- Modal Reportes -->
<div class="modal fade" id="modalReportes" tabindex="-1" aria-labelledby="modalReportesLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalReportesLabel">
                    <i class="fas fa-file-export me-2"></i>Exportar Inventario
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form id="formExportar" onsubmit="exportarReporte(event)">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tipo de Contenido</label>
                        <select name="tipo_reporte" class="form-select" required>
                            <option value="">Seleccionar...</option>
                            <option value="general">📊 Inventario Completo</option>
                            <option value="equipos">💻 Solo Equipos</option>
                            <option value="insumos">📦 Solo Insumos</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Formato</label>
                        <select name="formato" class="form-select" required>
                            <option value="">Seleccionar...</option>
                            <option value="pdf">PDF</option>
                            <option value="excel">Excel</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="formExportar" class="btn btn-primary">Exportar</button>
            </div>
        </div>
    </div>
</div>

<!-- Barra de filtros -->
<div class="card shadow mb-4">
    <div class="card-header text-white bg-orange d-flex justify-content-between align-items-center">
        <h6 class="mb-0">
            <i class="fas fa-filter me-2"></i>Filtros de Inventario
        </h6>
        <a href="/inventario?categoria=" class="btn btn-sm btn-outline-light">
            <i class="fas fa-times me-1"></i>Limpiar filtros
        </a>
    </div>

    <div id="filtrosInventario">
        <div class="card-body">
            <form method="GET" action="/inventario" id="formFiltros">
                <div class="row g-3 align-items-end">
                    <!-- Buscar -->
                    <div class="col-md-2">
                        <label class="form-label">Buscar</label>
                        <input type="text" name="buscar" id="buscarInventario"
                               class="form-control form-control-sm"
                               placeholder="Buscar..."
                               value="{{ request('buscar') }}"
                               onkeypress="if(event.keyCode == 13) { this.form.submit(); }">
                    </div>

                    <!-- Categoría -->
                    <div class="col-md-2">
                        <label class="form-label">Categoría</label>
                        <select name="categoria" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="" {{ $categoria == '' ? 'selected' : '' }}>Todos</option>
                            <option value="Equipo" {{ $categoria == 'Equipo' ? 'selected' : '' }}>Equipo</option>
                            <option value="Insumo" {{ $categoria == 'Insumo' ? 'selected' : '' }}>Insumo</option>
                        </select>
                    </div>

                    <!-- Tipo -->
                    <div class="col-md-2">
                        <label class="form-label">Tipo</label>
                        <select name="tipo" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            @if($categoria == 'Insumo')
                                @foreach($nombresInsumos as $nombre)
                                    <option value="{{ $nombre }}" {{ request('tipo') == $nombre ? 'selected' : '' }}>
                                        {{ $nombre }}
                                    </option>
                                @endforeach
                            @else
                                @foreach($tipos as $tipo)
                                    <option value="{{ $tipo }}" {{ request('tipo') == $tipo ? 'selected' : '' }}>
                                        {{ $tipo }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <!-- Estado -->
                    <div class="col-md-2">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            @foreach($estados as $estado)
                                <option value="{{ $estado->nombre }}" {{ request('estado') == $estado->nombre ? 'selected' : '' }}>
                                    {{ $estado->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Usuario asignado -->
                    <div class="col-md-2">
                        <label class="form-label">Usuario asignado</label>
                        <select name="usuario" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            <option value="Sin asignar" {{ request('usuario') == 'Sin asignar' ? 'selected' : '' }}>Sin asignar</option>
                            @foreach($usuarios as $usuario)
                                <option value="{{ $usuario->nombre }}" {{ request('usuario') == $usuario->nombre ? 'selected' : '' }}>
                                    {{ $usuario->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Proveedor -->
                    <div class="col-md-2">
                        <label class="form-label">Proveedor</label>
                        <select name="proveedor" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            @foreach($proveedores as $prov)
                                <option value="{{ $prov->nombre }}" {{ request('proveedor') == $prov->nombre ? 'selected' : '' }}>
                                    {{ $prov->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Sucursal -->
                    <div class="col-md-2">
                        <label class="form-label">Sucursal</label>
                        <select name="sucursal" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Todas</option>
                            @foreach($sucursales as $sucursal)
                                <option value="{{ $sucursal->nombre }}" {{ request('sucursal') == $sucursal->nombre ? 'selected' : '' }}>
                                    {{ $sucursal->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Precio mínimo -->
                    <div class="col-md-2">
                        <label class="form-label">Precio mínimo</label>
                        <input type="number" name="precio_min" class="form-control form-control-sm"
                               value="{{ request('precio_min') }}"
                               onchange="this.form.submit()"
                               onkeypress="if(event.keyCode == 13) { this.form.submit(); }">
                    </div>

                    <!-- Precio máximo -->
                    <div class="col-md-2">
                        <label class="form-label">Precio máximo</label>
                        <input type="number" name="precio_max" class="form-control form-control-sm"
                               value="{{ request('precio_max') }}"
                               onchange="this.form.submit()"
                               onkeypress="if(event.keyCode == 13) { this.form.submit(); }">
                    </div>

                    <!-- Filtrar por -->
                    <div class="col-md-2">
                        <label class="form-label">Filtrar por</label>
                        <select name="fecha_tipo" class="form-select form-select-sm" id="fechaTipo" onchange="cambiarTipoFecha(); this.form.submit();">
                            <option value="registro" {{ request('fecha_tipo') == 'registro' ? 'selected' : '' }}>Fecha de Registro</option>
                            <option value="compra" {{ request('fecha_tipo') == 'compra' ? 'selected' : '' }}>Fecha de Compra</option>
                            <option value="garantia" {{ request('fecha_tipo') == 'garantia' ? 'selected' : '' }}>Garantía</option>
                        </select>
                    </div>

                    <!-- Desde -->
                    <div class="col-md-2" id="fechaDesdeContainer">
                        <label class="form-label">Desde</label>
                        <input type="date" name="fecha_desde" class="form-control form-control-sm"
                               value="{{ request('fecha_desde') }}" onchange="this.form.submit()">
                    </div>

                    <!-- Hasta -->
                    <div class="col-md-2" id="fechaHastaContainer">
                        <label class="form-label">Hasta</label>
                        <input type="date" name="fecha_hasta" class="form-control form-control-sm"
                               value="{{ request('fecha_hasta') }}" onchange="this.form.submit()">
                    </div>

                    <!-- Garantía mínima -->
                    <div class="col-md-2 d-none" id="garantiaMinContainer">
                        <label class="form-label">Mín. meses garantía</label>
                        <input type="number" name="garantia_min" class="form-control form-control-sm"
                               value="{{ request('garantia_min') }}" 
                               placeholder="0"
                               onchange="this.form.submit()"
                               onkeypress="if(event.keyCode == 13) { this.form.submit(); }">
                    </div>

                    <!-- Garantía máxima -->
                    <div class="col-md-2 d-none" id="garantiaMaxContainer">
                        <label class="form-label">Máx. meses garantía</label>
                        <input type="number" name="garantia_max" class="form-control form-control-sm"
                               value="{{ request('garantia_max') }}" 
                               placeholder="12"
                               onchange="this.form.submit()"
                               onkeypress="if(event.keyCode == 13) { this.form.submit(); }">
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Tabla de Inventario con asignaciones -->
<form method="POST" action="{{ route('inventario.asignaciones') }}" id="formAsignaciones">
    @csrf
    <div class="card shadow">
        <div class="card-header text-white bg-orange d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Inventario</h5>
            <div class="d-flex align-items-center">
                <select id="tipoAsignacion" name="tipo_asignacion" class="form-select me-2 w-auto">
                    <option value="usuario">Asignar a Usuario</option>
                    <option value="sucursal">Asignar a Sucursal</option>
                    <option value="ninguno">Quitar asignación</option>
                </select>
                <select id="selectorAsignacion" name="destino_id" class="form-select w-auto me-2"></select>
                
                <!-- Campo de cantidad para insumos -->
                <div id="cantidadContainer" class="d-none me-2" style="width: 120px;">
                    <input type="number" name="cantidad" id="cantidadAsignacion" 
                           class="form-control form-control-sm" 
                           placeholder="Cantidad" 
                           min="1">
                </div>
                
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check me-2"></i>Aplicar a seleccionados
                </button>
            </div>
        </div>

        <div class="card-body table-responsive">
            <table class="table table-hover align-middle inventario-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>Categoría</th>
                        <th>Tipo / Nombre</th>

                        @if($categoria == 'Equipo' || $categoria == '')
                            <th>Marca</th>
                            <th>Modelo</th>
                        @endif

                        @if($categoria == 'Insumo' || $categoria == '')
                            <th>Cantidad</th>
                        @endif

                        <th>Precio</th>
                        <th>Sucursal</th>
                        <th>Estado</th>
                        <th>Usuario asignado</th>
                        <th>Proveedor</th>
                        <th>
                            @if(request('fecha_tipo') == 'compra')
                                Fecha de Compra
                            @elseif(request('fecha_tipo') == 'garantia')
                                Garantía
                            @else
                                Fecha de Registro
                            @endif
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Equipos --}}
                    @if($categoria == 'Equipo' || $categoria == '')
                        @foreach($equipos as $equipo)
                            @if(request('estado') == 'Baja' || ($equipo->estadoEquipo->nombre != 'Baja'))
                                <tr onclick="window.location='{{ route('inventario.equipo', $equipo->id) }}'" style="cursor:pointer;">
                                    <td>
                                        <input type="checkbox" name="items[]" value="{{ $equipo->id }}" 
                                               class="item-checkbox"
                                               data-tipo="equipo"
                                               onclick="event.stopPropagation();">
                                    </td>
                                    <td><span class="badge bg-primary">Equipo</span></td>
                                    <td>{{ $equipo->tipoEquipo->nombre ?? '-' }}</td>

                                    @if($categoria == 'Equipo' || $categoria == '')
                                        <td>{{ $equipo->marca }}</td>
                                        <td>{{ $equipo->modelo }}</td>
                                    @endif

                                    @if($categoria == 'Insumo' || $categoria == '')
                                        <td>-</td>
                                    @endif

                                    <td>{{ number_format($equipo->precio, 0, ',', '.') }} CLP</td>
                                    <td>{{ $equipo->sucursal->nombre ?? '-' }}</td>
                                    <td>
                                        @php
                                            $estadoNombre = $equipo->estadoEquipo->nombre ?? 'Sin estado';
                                            $coloresEstadosEquipo = [
                                                'Disponible' => 'bg-success',
                                                'Asignado'   => 'bg-primary',
                                                'Mantención' => 'bg-warning',
                                                'Baja'       => 'bg-danger',
                                                'En tránsito'=> 'bg-secondary',
                                            ];
                                            $color = $coloresEstadosEquipo[$estadoNombre] ?? 'bg-info';
                                        @endphp
                                        <span class="badge {{ $color }}">{{ $estadoNombre }}</span>
                                    </td>
                                    <td>{{ $equipo->usuarioAsignado?->usuario?->nombre ?? '-' }}</td>
                                    <td>{{ $equipo->proveedor->nombre ?? 'N/A' }}</td>
                                    <td>
                                        @if(request('fecha_tipo') == 'compra')
                                            {{ $equipo->fecha_compra ? \Carbon\Carbon::parse($equipo->fecha_compra)->format('d-m-Y') : '-' }}
                                        @elseif(request('fecha_tipo') == 'garantia')
                                            @php
                                                $garantia = $equipo->documentos->where('tipo', 'garantia')->sortByDesc('tiempo_garantia_meses')->first();
                                            @endphp
                                            @if($garantia)
                                                @php
                                                    $fechaCompra = \Carbon\Carbon::parse($equipo->fecha_compra);
                                                    $vence = $fechaCompra->copy()->addMonths($garantia->tiempo_garantia_meses);
                                                    $diasRestantes = now()->diffInDays($vence, false);
                                                    
                                                    if ($diasRestantes < 0) {
                                                        $estadoGarantia = 'vencida';
                                                    } elseif ($diasRestantes == 0) {
                                                        $estadoGarantia = 'por_vencer_dia';
                                                    } elseif ($diasRestantes <= 7) {
                                                        $estadoGarantia = 'por_vencer_semana';
                                                    } elseif ($diasRestantes <= 30) {
                                                        $estadoGarantia = 'por_vencer_mes';
                                                    } else {
                                                        $estadoGarantia = 'vigente';
                                                    }
                                                    
                                                    $coloresEstadosGarantia = [
                                                        'vigente' => 'bg-success',
                                                        'por_vencer_mes' => 'bg-warning',
                                                        'por_vencer_semana' => 'bg-warning',
                                                        'por_vencer_dia' => 'bg-danger',
                                                        'vencida' => 'bg-secondary'
                                                    ];
                                                    
                                                    $badgeColor = $coloresEstadosGarantia[$estadoGarantia] ?? 'bg-light text-dark';
                                                @endphp
                                                <span class="badge {{ $badgeColor }}" 
                                                      title="{{ $garantia->tiempo_garantia_meses }} meses de garantía"
                                                      data-bs-toggle="tooltip">
                                                    {{ formatearDuracionGarantia(\Carbon\Carbon::parse($equipo->fecha_compra), $garantia->tiempo_garantia_meses) }}
                                                </span>
                                            @else
                                                <span class="badge bg-light text-dark">Sin garantía</span>
                                            @endif
                                        @else
                                            {{ \Carbon\Carbon::parse($equipo->fecha_registro)->format('d-m-Y') }}
                                        @endif
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    @endif

                    {{-- Insumos --}}
                    @if($categoria == 'Insumo' || $categoria == '')
                        @foreach($insumos as $insumo)
                            @if(request('estado') == 'Baja' || ($insumo->estadoEquipo->nombre != 'Baja'))
                                <tr onclick="window.location='{{ route('inventario.insumo', $insumo->id) }}'" style="cursor:pointer;">
                                    <td>
                                        <input type="checkbox" name="items[]" value="{{ $insumo->id }}"
                                               class="item-checkbox"
                                               data-tipo="insumo"
                                               data-cantidad="{{ $insumo->cantidad }}"
                                               onclick="event.stopPropagation();">
                                    </td>
                                    <td><span class="badge bg-success">Insumo</span></td>
                                    <td>{{ $insumo->nombre }}</td>

                                    @if($categoria == 'Equipo' || $categoria == '')
                                        <td>-</td>
                                        <td>-</td>
                                    @endif

                                    @if($categoria == 'Insumo' || $categoria == '')
                                        <td>{{ $insumo->cantidad }}</td>
                                    @endif

                                    <td>{{ number_format($insumo->precio, 0, ',', '.') }} CLP</td>
                                    <td>{{ $insumo->sucursal->nombre ?? '-' }}</td>
                                    <td>
                                        @php
                                            $estadoNombre = $insumo->estadoEquipo->nombre ?? 'Sin estado';
                                            $coloresEstadosInsumo = [
                                                'Disponible' => 'bg-success',
                                                'Asignado'   => 'bg-primary',
                                                'Mantención' => 'bg-warning',
                                                'Baja'       => 'bg-danger',
                                                'En tránsito'=> 'bg-secondary',
                                            ];
                                            $color = $coloresEstadosInsumo[$estadoNombre] ?? 'bg-info';
                                        @endphp
                                        <span class="badge {{ $color }}">{{ $estadoNombre }}</span>
                                    </td>
                                    <td>{{ $insumo->usuarioAsignado?->usuario?->nombre ?? '-' }}</td>
                                    <td>{{ $insumo->proveedor->nombre ?? 'N/A' }}</td>
                                    <td>
                                        @if(request('fecha_tipo') == 'compra')
                                            {{ $insumo->fecha_compra ? \Carbon\Carbon::parse($insumo->fecha_compra)->format('d-m-Y') : '-' }}
                                        @elseif(request('fecha_tipo') == 'garantia')
                                            @php
                                                $garantia = $insumo->documentos->where('tipo', 'garantia')->sortByDesc('tiempo_garantia_meses')->first();
                                            @endphp
                                            @if($garantia)
                                                @php
                                                    $fechaCompra = \Carbon\Carbon::parse($insumo->fecha_compra);
                                                    $vence = $fechaCompra->copy()->addMonths($garantia->tiempo_garantia_meses);
                                                    $diasRestantes = now()->diffInDays($vence, false);
                                                    
                                                    if ($diasRestantes < 0) {
                                                        $estadoGarantia = 'vencida';
                                                    } elseif ($diasRestantes == 0) {
                                                        $estadoGarantia = 'por_vencer_dia';
                                                    } elseif ($diasRestantes <= 7) {
                                                        $estadoGarantia = 'por_vencer_semana';
                                                    } elseif ($diasRestantes <= 30) {
                                                        $estadoGarantia = 'por_vencer_mes';
                                                    } else {
                                                        $estadoGarantia = 'vigente';
                                                    }
                                                    
                                                    $coloresEstadosGarantia = [
                                                        'vigente' => 'bg-success',
                                                        'por_vencer_mes' => 'bg-warning',
                                                        'por_vencer_semana' => 'bg-warning',
                                                        'por_vencer_dia' => 'bg-danger',
                                                        'vencida' => 'bg-secondary'
                                                    ];
                                                    
                                                    $badgeColor = $coloresEstadosGarantia[$estadoGarantia] ?? 'bg-light text-dark';
                                                @endphp
                                                <span class="badge {{ $badgeColor }}" 
                                                      title="{{ $garantia->tiempo_garantia_meses }} meses de garantía"
                                                      data-bs-toggle="tooltip">
                                                    {{ formatearDuracionGarantia(\Carbon\Carbon::parse($insumo->fecha_compra), $garantia->tiempo_garantia_meses) }}
                                                </span>
                                            @else
                                                <span class="badge bg-light text-dark">Sin garantía</span>
                                            @endif
                                        @else
                                            {{ \Carbon\Carbon::parse($insumo->fecha_registro)->format('d-m-Y') }}
                                        @endif
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</form>

@push('scripts')
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    cambiarTipoFecha();

    // Autocomplete
    const $input = $('#buscarInventario');
    if ($input.length && typeof $.ui !== 'undefined') {
        $input.autocomplete({
            minLength: 2,
            delay: 300,
            source: function(request, response) {
                $.ajax({
                    url: "{{ route('inventario.autocomplete') }}",
                    method: "GET",
                    dataType: "json",
                    data: { term: request.term },
                    success: function(data) {
                        response(data);
                    },
                    error: function(xhr, status, error) {
                        console.error("Error en autocomplete:", error);
                        response([]);
                    }
                });
            },
            select: function(event, ui) {
                const campo = ui.item.campo;
                const valor = ui.item.value;
                
                if (campo === 'buscar') {
                    $input.val(valor);
                    $('#formFiltros').submit();
                } else {
                    $input.val('');
                    const $select = $(`select[name="${campo}"]`);
                    if ($select.length) {
                        $select.val(valor).trigger('change');
                    } else {
                        $('#formFiltros').submit();
                    }
                }
                
                return false;
            },
            focus: function(event, ui) {
                event.preventDefault();
            }
        });
    }

    // Selector dinámico de asignación
    const tipoAsignacion = document.getElementById('tipoAsignacion');
    const selectorAsignacion = document.getElementById('selectorAsignacion');
    
    const usuarios = @json($usuarios->map(function($user) { return ['id' => $user->id, 'nombre' => $user->nombre]; }));
    const sucursales = @json($sucursales->map(function($suc) { return ['id' => $suc->id, 'nombre' => $suc->nombre]; }));

    function cargarOpciones(tipo) {
        selectorAsignacion.innerHTML = '';
        
        if (tipo === 'ninguno') {
            let opt = document.createElement('option');
            opt.value = '';
            opt.textContent = 'Confirmar desasignación';
            selectorAsignacion.appendChild(opt);
            return;
        }
        
        let opciones = tipo === 'usuario' ? usuarios : sucursales;
        
        let defaultOpt = document.createElement('option');
        defaultOpt.value = '';
        defaultOpt.textContent = tipo === 'usuario' ? 'Seleccionar usuario' : 'Seleccionar sucursal';
        selectorAsignacion.appendChild(defaultOpt);
        
        opciones.forEach(op => {
            let opt = document.createElement('option');
            opt.value = op.id;
            opt.textContent = op.nombre;
            selectorAsignacion.appendChild(opt);
        });
    }

    if (tipoAsignacion && selectorAsignacion) {
        cargarOpciones(tipoAsignacion.value);
        tipoAsignacion.addEventListener('change', e => {
            cargarOpciones(e.target.value);
            toggleCantidadField();
        });
    }

    // Mostrar/ocultar campo de cantidad
    function toggleCantidadField() {
        const tipoAsignacion = document.getElementById('tipoAsignacion').value;
        const cantidadContainer = document.getElementById('cantidadContainer');
        const cantidadInput = document.getElementById('cantidadAsignacion');
        
        if (tipoAsignacion === 'usuario') {
            const insumosSeleccionados = document.querySelectorAll('input.item-checkbox[data-tipo="insumo"]:checked');
            
            if (insumosSeleccionados.length > 0) {
                cantidadContainer.classList.remove('d-none');
                cantidadInput.required = true;
                
                if (insumosSeleccionados.length === 1) {
                    const cantidadMaxima = insumosSeleccionados[0].getAttribute('data-cantidad');
                    cantidadInput.setAttribute('max', cantidadMaxima);
                    cantidadInput.setAttribute('placeholder', `Máx: ${cantidadMaxima}`);
                } else {
                    cantidadInput.removeAttribute('max');
                    cantidadInput.setAttribute('placeholder', 'Cantidad');
                }
            } else {
                cantidadContainer.classList.add('d-none');
                cantidadInput.required = false;
                cantidadInput.value = '';
            }
        } else {
            cantidadContainer.classList.add('d-none');
            cantidadInput.required = false;
            cantidadInput.value = '';
        }
    }

    document.getElementById('tipoAsignacion')?.addEventListener('change', toggleCantidadField);

    document.addEventListener('change', function(e) {
        if (e.target.matches('input.item-checkbox')) {
            toggleCantidadField();
        }
    });

    // Validación del formulario
    document.getElementById('formAsignaciones')?.addEventListener('submit', function(e) {
        const checkboxes = document.querySelectorAll('input.item-checkbox:checked');
        const tipo = document.getElementById('tipoAsignacion').value;
        const destino = document.getElementById('selectorAsignacion').value;
        const cantidadInput = document.getElementById('cantidadAsignacion');
        
        if (checkboxes.length === 0) {
            e.preventDefault();
            alert('Por favor, selecciona al menos un elemento del inventario.');
            return;
        }
        
        if (tipo !== 'ninguno' && !destino) {
            e.preventDefault();
            alert('Por favor, selecciona un destino para la asignación.');
            return;
        }
        
        if (tipo === 'usuario' && !cantidadContainer.classList.contains('d-none')) {
            const cantidad = parseInt(cantidadInput.value);
            if (!cantidad || cantidad < 1) {
                e.preventDefault();
                alert('Por favor, ingresa una cantidad válida para los insumos.');
                return;
            }
            
            const insumosSeleccionados = document.querySelectorAll('input.item-checkbox[data-tipo="insumo"]:checked');
            if (insumosSeleccionados.length === 1) {
                const cantidadMaxima = parseInt(insumosSeleccionados[0].getAttribute('data-cantidad'));
                if (cantidad > cantidadMaxima) {
                    e.preventDefault();
                    alert(`La cantidad ingresada (${cantidad}) excede el stock disponible (${cantidadMaxima}).`);
                    return;
                }
            }
        }
        
        const accion = tipo === 'usuario' ? 'asignar usuario' : 
                       tipo === 'sucursal' ? 'cambiar sucursal' : 'quitar asignación';
        
        if (!confirm(`¿Estás seguro de que deseas ${accion} para ${checkboxes.length} elementos seleccionados?`)) {
            e.preventDefault();
        }
    });

    // SelectAll
    document.getElementById('selectAll')?.addEventListener('change', function(e) {
        document.querySelectorAll('input.item-checkbox').forEach(cb => {
            cb.checked = e.target.checked;
        });
        toggleCantidadField();
    });

    // Tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Función para exportar reporte
function exportarReporte(event) {
    event.preventDefault();
    const form = event.target;
    const tipoReporte = form.tipo_reporte.value;
    const formato = form.formato.value;
    
    if (!tipoReporte || !formato) {
        alert('Por favor, selecciona el tipo de reporte y formato.');
        return;
    }
    
    // Simular exportación
    alert(`📊 Exportación en desarrollo:\n\nTipo: ${tipoReporte}\nFormato: ${formato.toUpperCase()}\n\nEsta funcionalidad estará disponible próximamente.`);
    
    // Cerrar el modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('modalReportes'));
    modal.hide();
    
    // Limpiar el formulario
    form.reset();
}

function cambiarTipoFecha() {
    const fechaTipo = document.getElementById('fechaTipo').value;
    const fechaDesdeContainer = document.getElementById('fechaDesdeContainer');
    const fechaHastaContainer = document.getElementById('fechaHastaContainer');
    const garantiaMinContainer = document.getElementById('garantiaMinContainer');
    const garantiaMaxContainer = document.getElementById('garantiaMaxContainer');
    
    if (fechaTipo === 'garantia') {
        fechaDesdeContainer.classList.add('d-none');
        fechaHastaContainer.classList.add('d-none');
        garantiaMinContainer.classList.remove('d-none');
        garantiaMaxContainer.classList.remove('d-none');
    } else {
        fechaDesdeContainer.classList.remove('d-none');
        fechaHastaContainer.classList.remove('d-none');
        garantiaMinContainer.classList.add('d-none');
        garantiaMaxContainer.classList.add('d-none');
    }
}

function abrirScannerQR() {
    alert('Funcionalidad de escáner QR - Por implementar');
}
</script>
@endpush

@endsection