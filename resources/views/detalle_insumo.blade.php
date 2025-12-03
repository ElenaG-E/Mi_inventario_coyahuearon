@extends('layouts.base')

@section('contenido')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Detalle del Insumo - IN{{ $insumo->id }}</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('inventario') }}" class="btn btn-sm btn-outline-success me-2">
            <i class="fas fa-arrow-left me-1"></i>Volver al Inventario
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- Información del Insumo -->
        <div class="card shadow mb-4">
            <div class="card-header text-white bg-success">
                <h5 class="card-title mb-0">
                    <i class="fas fa-box me-2"></i>{{ $insumo->nombre }}
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Nombre:</strong> {{ $insumo->nombre }}</p>
                        <p><strong>Cantidad:</strong> {{ $insumo->cantidad }}</p>
                        <p><strong>Precio Unitario:</strong> {{ number_format($insumo->precio, 0, ',', '.') }} CLP</p>
                        <p><strong>Fecha de Compra:</strong> {{ \Carbon\Carbon::parse($insumo->fecha_compra)->format('d/m/Y') }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Estado:</strong>
                            <span class="badge bg-{{ match($insumo->estadoEquipo->nombre) {
                                'Disponible' => 'success',
                                'Asignado' => 'primary',
                                'Mantención' => 'warning',
                                'Baja' => 'danger',
                                'En tránsito' => 'secondary',
                                default => 'info',
                            } }}">
                                {{ $insumo->estadoEquipo->nombre }}
                            </span>
                        </p>
                        <p><strong>Proveedor:</strong> {{ $insumo->proveedor->nombre ?? 'N/A' }}</p>
                        <!-- ✅ Usuario asignado actual -->
                        <p><strong>Usuario Asignado:</strong> {{ $insumo->usuarioAsignado?->usuario?->nombre ?? 'Sin asignar' }}</p>
                        <p><strong>Sucursal:</strong> {{ $insumo->sucursal->nombre ?? '-' }}</p>
                        <p><strong>Fecha de Registro:</strong> {{ \Carbon\Carbon::parse($insumo->fecha_registro)->format('d/m/Y') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Garantía -->
        @php
            $garantia = $insumo->documentos->where('tipo', 'garantia')->sortByDesc('tiempo_garantia_meses')->first();
        @endphp

        @if($garantia)
        <div class="card shadow mb-4">
            <div class="card-header text-white bg-success">
                <h5 class="card-title mb-0"><i class="fas fa-shield-alt me-2"></i>Garantía</h5>
            </div>
            <div class="card-body">
                <p><strong>Duración:</strong> {{ $garantia->tiempo_garantia_meses }} meses</p>
                <p><strong>Vence:</strong>
                    {{ formatearDuracionGarantia(\Carbon\Carbon::parse($insumo->fecha_compra), $garantia->tiempo_garantia_meses) }}
                </p>
            </div>
        </div>
        @endif

        <!-- Documentos Asociados -->
        @if($insumo->documentos->count())
        <div class="card shadow mb-4">
            <div class="card-header text-white bg-dark">
                <h5 class="card-title mb-0"><i class="fas fa-file-alt me-2"></i>Documentos Asociados</h5>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    @foreach($insumo->documentos as $doc)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-file me-2"></i>{{ $doc->nombre_archivo }}
                                <small class="text-muted">({{ ucfirst($doc->tipo) }})</small>
                            </div>
                            <span class="badge bg-secondary">{{ \Carbon\Carbon::parse($doc->fecha_subida)->format('d/m/Y') }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        <!-- Historial de Movimientos -->
        <div class="card shadow mb-4">
            <div class="card-header text-white bg-success">
                <h5 class="card-title mb-0"><i class="fas fa-route me-2"></i>Historial de Movimientos</h5>
            </div>
            <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                <div class="timeline">
                    @forelse($insumo->movimientos as $mov)
                        <div class="timeline-item mb-3">
                            <div class="d-flex">
                                <div class="timeline-marker bg-primary rounded-circle me-3"
                                    style="width: 12px; height: 12px; margin-top: 5px;"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-1">{{ $mov->tipo_movimiento }}</h6>
                                    <p class="text-muted mb-1">
                                        {{ \Carbon\Carbon::parse($mov->fecha_movimiento)->format('d/m/Y - h:i A') }}
                                    </p>
                                    @if($mov->comentario)
                                        <p class="mb-0"><em>{{ $mov->comentario }}</em></p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted">No hay movimientos registrados.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Historial de Asignaciones -->
        <div class="card shadow mb-4">
            <div class="card-header text-white bg-secondary d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="fas fa-user-clock me-2"></i>Historial de Asignaciones</h5>
                <span class="badge bg-light text-dark">Stock: {{ $insumo->cantidad }}</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Cantidad Asignada</th>
                                <th>Cantidad Devuelta</th>
                                <th>Fecha Asignación</th>
                                <th>Fecha Fin</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($insumo->asignaciones->sortByDesc('fecha_asignacion') as $asignacion)
                                <tr>
                                    <td>
                                        <strong>{{ $asignacion->usuario->nombre ?? 'Usuario desconocido' }}</strong>
                                        @if($asignacion->motivo)
                                            <br><small class="text-muted">{{ $asignacion->motivo }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">{{ $asignacion->cantidad }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $asignacion->cantidad_devuelta ? 'success' : 'secondary' }}">
                                            {{ $asignacion->cantidad_devuelta ?? 0 }}
                                        </span>
                                    </td>
                                    <td>
                                        <small>{{ \Carbon\Carbon::parse($asignacion->fecha_asignacion)->format('d/m/Y') }}</small>
                                    </td>
                                    <td>
                                        @if($asignacion->fecha_fin)
                                            <small>{{ \Carbon\Carbon::parse($asignacion->fecha_fin)->format('d/m/Y') }}</small>
                                        @else
                                            <span class="badge bg-success">Activa</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($asignacion->fecha_fin)
                                            <span class="badge bg-secondary">Finalizada</span>
                                        @else
                                            <span class="badge bg-success">Activa</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!$asignacion->fecha_fin && $asignacion->cantidad > 0)
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-warning" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#modalDevolverInsumo"
                                                        data-asignacion-id="{{ $asignacion->id }}"
                                                        data-cantidad-maxima="{{ $asignacion->cantidad }}"
                                                        data-usuario-nombre="{{ $asignacion->usuario->nombre ?? '' }}">
                                                    <i class="fas fa-undo me-1"></i>Devolver
                                                </button>
                                                <button type="button" class="btn btn-outline-danger"
                                                        onclick="eliminarAsignacion({{ $asignacion->id }})">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        @else
                                            <button type="button" class="btn btn-outline-danger btn-sm"
                                                    onclick="eliminarAsignacion({{ $asignacion->id }})">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-3">
                                        No hay asignaciones registradas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Acciones -->
    <div class="col-lg-4">
        <div class="card shadow">
            <div class="card-header text-white bg-success">
                <h5 class="card-title mb-0"><i class="fas fa-bolt me-2"></i>Acciones Rápidas</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalGestionInsumo">
                        <i class="fas fa-edit me-2"></i>Editar / Asignar
                    </button>
                    <button class="btn btn-outline-info">
                        <i class="fas fa-file-pdf me-2"></i>Generar Reporte
                    </button>
                    <form method="POST" action="{{ route('insumo.destroy', $insumo->id) }}"
                        onsubmit="return confirm('¿Estás seguro de que deseas eliminar este insumo?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="fas fa-trash me-2"></i>Eliminar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Devolver Insumo -->
<div class="modal fade" id="modalDevolverInsumo" tabindex="-1" aria-labelledby="modalDevolverInsumoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="modalDevolverInsumoLabel">
                    <i class="fas fa-undo me-2"></i>Devolver Insumo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form method="POST" action="{{ route('insumo.desasignar', $insumo->id) }}">
                @csrf
                <input type="hidden" name="asignacion_id" id="asignacion_id">
                <div class="modal-body">
                    <p id="texto-devolucion"></p>
                    
                    <div class="mb-3">
                        <label for="cantidad_devolver" class="form-label">Cantidad a devolver</label>
                        <input type="number" 
                               class="form-control" 
                               id="cantidad_devolver" 
                               name="cantidad_devolver" 
                               min="1" 
                               value="1" 
                               required>
                        <div class="form-text" id="cantidad-maxima-texto"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="motivo_devolucion" class="form-label">Motivo de la devolución (opcional)</label>
                        <textarea class="form-control" 
                                  id="motivo_devolucion" 
                                  name="motivo_devolucion" 
                                  rows="2" 
                                  placeholder="Ej: Proyecto finalizado, sobrante..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-undo me-1"></i>Devolver
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@include('partials.modal-gestion-insumo', [
    'insumo' => $insumo,
    'estados' => $estados,
    'usuarios' => $usuarios,
    'sucursales' => $sucursales,
    'proveedores' => $proveedores
])

@push('scripts')
<script>
function eliminarAsignacion(asignacionId) {
    if (confirm('¿Estás seguro de que deseas eliminar permanentemente esta asignación? Esta acción no se puede deshacer.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("insumo.eliminar-asignacion", $insumo->id) }}';
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        
        const methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = 'DELETE';
        
        const asignacionField = document.createElement('input');
        asignacionField.type = 'hidden';
        asignacionField.name = 'asignacion_id';
        asignacionField.value = asignacionId;
        
        form.appendChild(csrfToken);
        form.appendChild(methodField);
        form.appendChild(asignacionField);
        document.body.appendChild(form);
        form.submit();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const modalDevolver = document.getElementById('modalDevolverInsumo');
    
    if (modalDevolver) {
        modalDevolver.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const asignacionId = button.getAttribute('data-asignacion-id');
            const cantidadMaxima = button.getAttribute('data-cantidad-maxima');
            const usuarioNombre = button.getAttribute('data-usuario-nombre');
            
            // Actualizar el formulario
            document.getElementById('asignacion_id').value = asignacionId;
            document.getElementById('cantidad_devolver').setAttribute('max', cantidadMaxima);
            document.getElementById('cantidad_devolver').value = 1;
            
            // Actualizar textos
            document.getElementById('texto-devolucion').textContent = 
                `Devolver insumos asignados a: ${usuarioNombre}`;
            document.getElementById('cantidad-maxima-texto').textContent = 
                `Máximo a devolver: ${cantidadMaxima} unidades`;
        });
    }
    
    // Validación del formulario de devolución
    const formDevolver = document.querySelector('form[action*="desasignar"]');
    if (formDevolver) {
        formDevolver.addEventListener('submit', function(e) {
            const cantidadInput = document.getElementById('cantidad_devolver');
            const cantidadMaxima = parseInt(cantidadInput.getAttribute('max'));
            const cantidad = parseInt(cantidadInput.value);
            
            if (cantidad > cantidadMaxima) {
                e.preventDefault();
                alert(`La cantidad a devolver (${cantidad}) no puede ser mayor a la cantidad asignada (${cantidadMaxima}).`);
                return false;
            }
            
            if (cantidad < 1) {
                e.preventDefault();
                alert('La cantidad a devolver debe ser al menos 1.');
                return false;
            }
            
            if (!confirm(`¿Estás seguro de que deseas devolver ${cantidad} unidades?`)) {
                e.preventDefault();
                return false;
            }
        });
    }
});
</script>
@endpush