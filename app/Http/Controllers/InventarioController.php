<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\Insumo;
use App\Models\TipoEquipo;
use App\Models\EstadoEquipo;
use App\Models\Proveedor;
use App\Models\Sucursal;
use App\Models\Usuario;
use App\Models\Asignacion;
use App\Models\AsignacionInsumo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class InventarioController extends Controller
{
    public function index(Request $request)
    {
        $categoria   = $request->get('categoria', '');
        $tipo        = $request->get('tipo', '');
        $estado      = $request->get('estado', '');
        $usuario     = $request->get('usuario', '');
        $proveedor   = $request->get('proveedor', '');
        $sucursal    = $request->get('sucursal', '');
        $precioMin   = $request->get('precio_min', '');
        $precioMax   = $request->get('precio_max', '');
        $fechaTipo   = $request->get('fecha_tipo', 'registro');
        $fechaDesde  = $request->get('fecha_desde', '');
        $fechaHasta  = $request->get('fecha_hasta', '');
        $garantiaMin = $request->get('garantia_min', '');
        $garantiaMax = $request->get('garantia_max', '');
        $buscar      = $request->get('buscar', '');

        $equipos = Equipo::with(['tipoEquipo','estadoEquipo','proveedor','usuarioAsignado.usuario','sucursal','documentos'])
            ->when($tipo, fn($q) => $q->whereHas('tipoEquipo', fn($t) => $t->where('nombre', $tipo)))
            ->when($estado, function($q) use ($estado) {
                if ($estado === 'Baja') {
                    $q->whereHas('estadoEquipo', fn($e) => $e->where('nombre','Baja'));
                } elseif ($estado) {
                    $q->whereHas('estadoEquipo', fn($e) => $e->where('nombre',$estado));
                }
            }, function($q) {
                $q->whereHas('estadoEquipo', fn($e) => $e->where('nombre','!=','Baja'));
            })
            ->when($usuario === 'Sin asignar', fn($q) => $q->whereDoesntHave('usuarioAsignado'))
            ->when($usuario && $usuario !== 'Sin asignar', fn($q) => $q->whereHas('usuarioAsignado.usuario', fn($u) => $u->where('nombre', $usuario)))
            ->when($proveedor, fn($q) => $q->whereHas('proveedor', fn($p) => $p->where('nombre', $proveedor)))
            ->when($sucursal, fn($q) => $q->whereHas('sucursal', fn($s) => $s->where('nombre', $sucursal)))
            ->when($precioMin, fn($q) => $q->where('precio', '>=', $precioMin))
            ->when($precioMax, fn($q) => $q->where('precio', '<=', $precioMax))
            ->when($fechaDesde && $fechaTipo !== 'garantia', fn($q) => $q->whereDate($fechaTipo == 'compra' ? 'fecha_compra' : 'fecha_registro', '>=', $fechaDesde))
            ->when($fechaHasta && $fechaTipo !== 'garantia', fn($q) => $q->whereDate($fechaTipo == 'compra' ? 'fecha_compra' : 'fecha_registro', '<=', $fechaHasta))
            ->when($fechaTipo === 'garantia', function($q) use ($garantiaMin, $garantiaMax) {
                $q->whereHas('documentos', function($docQuery) use ($garantiaMin, $garantiaMax) {
                    $docQuery->where('tipo', 'garantia')
                        ->when($garantiaMin, fn($q) => $q->where('tiempo_garantia_meses', '>=', $garantiaMin))
                        ->when($garantiaMax, fn($q) => $q->where('tiempo_garantia_meses', '<=', $garantiaMax));
                });
            })
            ->when($buscar, function($q) use ($buscar) {
                $q->where(function($sub) use ($buscar) {
                    $sub->where('marca', 'like', "%$buscar%")
                        ->orWhere('modelo', 'like', "%$buscar%")
                        ->orWhere('numero_serie', 'like', "%$buscar%")
                        ->orWhereHas('tipoEquipo', fn($t) => $t->where('nombre','like',"%$buscar%"))
                        ->orWhereHas('estadoEquipo', fn($e) => $e->where('nombre','like',"%$buscar%"))
                        ->orWhereHas('proveedor', fn($p) => $p->where('nombre','like',"%$buscar%"))
                        ->orWhereHas('usuarioAsignado.usuario', fn($u) => $u->where('nombre','like',"%$buscar%"));
                });
            })
            ->orderBy('fecha_registro','desc')
            ->get();

        $insumos = Insumo::with(['estadoEquipo','proveedor','usuarioAsignado.usuario','sucursal','documentos'])
            ->when($tipo, fn($q) => $q->where('nombre', $tipo))
            ->when($estado, function($q) use ($estado) {
                if ($estado === 'Baja') {
                    $q->whereHas('estadoEquipo', fn($e) => $e->where('nombre','Baja'));
                } elseif ($estado) {
                    $q->whereHas('estadoEquipo', fn($e) => $e->where('nombre',$estado));
                }
            }, function($q) {
                $q->whereHas('estadoEquipo', fn($e) => $e->where('nombre','!=','Baja'));
            })
            ->when($usuario === 'Sin asignar', fn($q) => $q->whereDoesntHave('usuarioAsignado'))
            ->when($usuario && $usuario !== 'Sin asignar', fn($q) => $q->whereHas('usuarioAsignado.usuario', fn($u) => $u->where('nombre', $usuario)))
            ->when($proveedor, fn($q) => $q->whereHas('proveedor', fn($p) => $p->where('nombre', $proveedor)))
            ->when($sucursal, fn($q) => $q->whereHas('sucursal', fn($s) => $s->where('nombre', $sucursal)))
            ->when($precioMin, fn($q) => $q->where('precio', '>=', $precioMin))
            ->when($precioMax, fn($q) => $q->where('precio', '<=', $precioMax))
            ->when($fechaDesde && $fechaTipo !== 'garantia', fn($q) => $q->whereDate($fechaTipo == 'compra' ? 'fecha_compra' : 'fecha_registro', '>=', $fechaDesde))
            ->when($fechaHasta && $fechaTipo !== 'garantia', fn($q) => $q->whereDate($fechaTipo == 'compra' ? 'fecha_compra' : 'fecha_registro', '<=', $fechaHasta))
            ->when($fechaTipo === 'garantia', function($q) use ($garantiaMin, $garantiaMax) {
                $q->whereHas('documentos', function($docQuery) use ($garantiaMin, $garantiaMax) {
                    $docQuery->where('tipo', 'garantia')
                        ->when($garantiaMin, fn($q) => $q->where('tiempo_garantia_meses', '>=', $garantiaMin))
                        ->when($garantiaMax, fn($q) => $q->where('tiempo_garantia_meses', '<=', $garantiaMax));
                });
            })
            ->when($buscar, function($q) use ($buscar) {
                $q->where(function($sub) use ($buscar) {
                    $sub->where('nombre', 'like', "%$buscar%")
                        ->orWhereHas('estadoEquipo', fn($e) => $e->where('nombre','like',"%$buscar%"))
                        ->orWhereHas('usuarioAsignado.usuario', fn($u) => $u->where('nombre','like',"%$buscar%"))
                        ->orWhereHas('proveedor', fn($p) => $p->where('nombre','like',"%$buscar%"))
                        ->orWhereHas('sucursal', fn($s) => $s->where('nombre','like',"%$buscar%"));
                });
            })
            ->orderBy('fecha_registro','desc')
            ->get();

        $tipos          = TipoEquipo::pluck('nombre');
        $nombresInsumos = Insumo::pluck('nombre')->unique();
        $estados        = EstadoEquipo::all();
        $usuarios       = Usuario::all();
        $proveedores    = Proveedor::all();
        $sucursales     = Sucursal::all();

        // Obtener notificaciones de garantías
        $notificacionesGarantias = $this->obtenerNotificacionesGarantias();
        
        // Obtener notificaciones de asignaciones desde sesión
        $notificacionesAsignaciones = session('notificaciones_asignaciones', []);
        
        // Combinar ambas notificaciones
        $notificaciones = array_merge($notificacionesGarantias, $notificacionesAsignaciones);
        
        // Ordenar por prioridad (urgente primero)
        usort($notificaciones, function($a, $b) {
            $prioridades = ['urgente' => 3, 'alta' => 2, 'media' => 1, 'baja' => 0];
            return ($prioridades[$b['prioridad']] ?? 0) - ($prioridades[$a['prioridad']] ?? 0);
        });

        return view('inventario', compact(
            'equipos',
            'insumos',
            'tipos',
            'nombresInsumos',
            'estados',
            'usuarios',
            'proveedores',
            'sucursales',
            'categoria',
            'notificaciones'
        ));
    }

    private function obtenerNotificacionesGarantias()
    {
        $notificaciones = [];
        
        // Verificar garantías por vencer en equipos
        $equipos = Equipo::with(['documentos', 'tipoEquipo'])->get();
        
        foreach ($equipos as $equipo) {
            $garantia = $equipo->documentos->where('tipo', 'garantia')->first();
            if ($garantia && $equipo->fecha_compra) {
                $fechaCompra = \Carbon\Carbon::parse($equipo->fecha_compra);
                $vence = $fechaCompra->copy()->addMonths($garantia->tiempo_garantia_meses);
                $diasRestantes = now()->diffInDays($vence, false);
                
                if ($diasRestantes == 0) {
                    $notificaciones[] = [
                        'mensaje' => "⚠️ GARANTÍA VENCE HOY: {$equipo->tipoEquipo->nombre} ({$equipo->marca} {$equipo->modelo})",
                        'prioridad' => 'urgente',
                        'fecha' => now()->format('d/m/Y H:i'),
                        'tipo' => 'garantia',
                        'equipo_id' => $equipo->id,
                        'url' => route('inventario.equipo', $equipo->id)
                    ];
                } elseif ($diasRestantes > 0 && $diasRestantes <= 7) {
                    $notificaciones[] = [
                        'mensaje' => "🔔 Garantía vence en {$diasRestantes} días: {$equipo->tipoEquipo->nombre}",
                        'prioridad' => 'alta',
                        'fecha' => now()->format('d/m/Y H:i'),
                        'tipo' => 'garantia',
                        'equipo_id' => $equipo->id,
                        'url' => route('inventario.equipo', $equipo->id)
                    ];
                } elseif ($diasRestantes > 0 && $diasRestantes <= 30) {
                    $notificaciones[] = [
                        'mensaje' => "ℹ️ Garantía vence en " . ceil($diasRestantes/30) . " meses: {$equipo->tipoEquipo->nombre}",
                        'prioridad' => 'media',
                        'fecha' => now()->format('d/m/Y H:i'),
                        'tipo' => 'garantia',
                        'equipo_id' => $equipo->id,
                        'url' => route('inventario.equipo', $equipo->id)
                    ];
                }
            }
        }
        
        return $notificaciones;
    }

    public function autocomplete(Request $request)
    {
        $term = trim($request->get('term', ''));
        
        if (empty($term) || strlen($term) < 2) {
            return response()->json([]);
        }

        $termLike = "%{$term}%";
        $results = [];

        try {
            // Buscar marcas únicas
            $marcas = Equipo::where('marca', 'LIKE', $termLike)
                ->distinct()
                ->pluck('marca')
                ->take(5);

            foreach ($marcas as $marca) {
                $results[] = [
                    'label' => $marca,
                    'value' => $marca,
                    'tipo'  => 'marca',
                    'campo' => 'buscar',
                    'id'    => null
                ];
            }

            // Buscar modelos únicos
            $modelos = Equipo::where('modelo', 'LIKE', $termLike)
                ->distinct()
                ->pluck('modelo')
                ->take(5);

            foreach ($modelos as $modelo) {
                $results[] = [
                    'label' => $modelo,
                    'value' => $modelo,
                    'tipo'  => 'modelo',
                    'campo' => 'buscar',
                    'id'    => null
                ];
            }

            // Buscar tipos de equipo
            $tiposEquipo = TipoEquipo::where('nombre', 'LIKE', $termLike)
                ->limit(5)
                ->get();

            foreach ($tiposEquipo as $tipo) {
                $results[] = [
                    'label' => $tipo->nombre,
                    'value' => $tipo->nombre,
                    'tipo'  => 'tipo',
                    'campo' => 'tipo',
                    'id'    => null
                ];
            }

            // Buscar en insumos - nombre
            $insumos = Insumo::where('nombre', 'LIKE', $termLike)
                ->limit(10)
                ->get();

            foreach ($insumos as $insumo) {
                $results[] = [
                    'label' => $insumo->nombre,
                    'value' => $insumo->nombre,
                    'tipo'  => 'insumo',
                    'campo' => 'buscar',
                    'id'    => $insumo->id
                ];
            }

            // Buscar estados
            $estados = EstadoEquipo::where('nombre', 'LIKE', $termLike)
                ->limit(5)
                ->get();

            foreach ($estados as $estado) {
                $results[] = [
                    'label' => $estado->nombre,
                    'value' => $estado->nombre,
                    'tipo'  => 'estado',
                    'campo' => 'estado',
                    'id'    => null
                ];
            }

            // Buscar proveedores
            $proveedores = Proveedor::where('nombre', 'LIKE', $termLike)
                ->limit(5)
                ->get();

            foreach ($proveedores as $proveedor) {
                $results[] = [
                    'label' => $proveedor->nombre,
                    'value' => $proveedor->nombre,
                    'tipo'  => 'proveedor',
                    'campo' => 'proveedor',
                    'id'    => null
                ];
            }

            // Buscar sucursales
            $sucursales = Sucursal::where('nombre', 'LIKE', $termLike)
                ->limit(5)
                ->get();

            foreach ($sucursales as $sucursal) {
                $results[] = [
                    'label' => $sucursal->nombre,
                    'value' => $sucursal->nombre,
                    'tipo'  => 'sucursal',
                    'campo' => 'sucursal',
                    'id'    => null
                ];
            }

            // Buscar usuarios
            $usuarios = Usuario::where('nombre', 'LIKE', $termLike)
                ->limit(5)
                ->get();

            foreach ($usuarios as $usuario) {
                $results[] = [
                    'label' => $usuario->nombre,
                    'value' => $usuario->nombre,
                    'tipo'  => 'usuario',
                    'campo' => 'usuario',
                    'id'    => null
                ];
            }

            // Eliminar duplicados
            $uniqueResults = [];
            $seen = [];
            foreach ($results as $result) {
                $key = $result['label'] . '|' . $result['campo'];
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $uniqueResults[] = $result;
                }
            }

            $finalResults = array_slice($uniqueResults, 0, 20);

            return response()->json($finalResults);

        } catch (\Exception $e) {
            \Log::error('Error en autocomplete: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    public function storeAsignaciones(Request $request)
    {
        $request->validate([
            'tipo_asignacion' => 'required|in:usuario,sucursal,ninguno',
            'items' => 'required|array',
            'cantidad' => 'nullable|integer|min:1',
        ]);

        $tipoAsignacion = $request->tipo_asignacion;
        $destinoId = $request->destino_id;
        $cantidad = $request->cantidad;
        $items = $request->items;

        try {
            DB::beginTransaction();

            foreach ($items as $itemId) {
                $equipo = Equipo::find($itemId);
                
                if ($equipo) {
                    $this->procesarAsignacionEquipo($equipo, $tipoAsignacion, $destinoId);
                    continue;
                }

                $insumo = Insumo::find($itemId);
                if ($insumo) {
                    $this->procesarAsignacionInsumo($insumo, $tipoAsignacion, $destinoId, $cantidad);
                }
            }

            DB::commit();

            // Agregar notificación de asignación masiva
            $notificaciones = session('notificaciones_asignaciones', []);
            $mensajeAsignacion = $this->generarMensajeNotificacion($tipoAsignacion, count($items), $destinoId, $cantidad);
            
            $notificaciones[] = [
                'mensaje' => $mensajeAsignacion,
                'prioridad' => 'media',
                'fecha' => now()->format('d/m/Y H:i'),
                'tipo' => 'asignacion',
                'url' => route('inventario')
            ];
            
            $notificaciones = array_slice($notificaciones, -10);
            session(['notificaciones_asignaciones' => $notificaciones]);

            $mensaje = $this->generarMensajeExito($tipoAsignacion, count($items), $cantidad);
            return redirect()->route('inventario')->with('success', $mensaje);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error en asignaciones masivas: ' . $e->getMessage());
            return redirect()->route('inventario')->with('error', 'Error al procesar las asignaciones: ' . $e->getMessage());
        }
    }

    private function generarMensajeNotificacion($tipoAsignacion, $cantidad, $destinoId, $cantidadInsumos = null)
    {
        $destinoNombre = '';
        
        if ($tipoAsignacion === 'usuario' && $destinoId) {
            $usuario = Usuario::find($destinoId);
            $destinoNombre = $usuario ? " a {$usuario->nombre}" : '';
        } elseif ($tipoAsignacion === 'sucursal' && $destinoId) {
            $sucursal = Sucursal::find($destinoId);
            $destinoNombre = $sucursal ? " a sucursal {$sucursal->nombre}" : '';
        }

        $cantidadTexto = $cantidadInsumos ? " (Cantidad: {$cantidadInsumos})" : '';

        $mensajes = [
            'usuario' => "✅ ASIGNACIÓN MASIVA: {$cantidad} elementos asignados{$destinoNombre}{$cantidadTexto}",
            'sucursal' => "🏢 CAMBIO DE SUCURSAL: {$cantidad} elementos movidos{$destinoNombre}",
            'ninguno' => "❌ DESASIGNACIÓN: {$cantidad} elementos desasignados"
        ];
        
        return $mensajes[$tipoAsignacion] ?? "📦 OPERACIÓN MASIVA: {$cantidad} elementos procesados";
    }

    private function procesarAsignacionEquipo(Equipo $equipo, $tipoAsignacion, $destinoId)
    {
        switch ($tipoAsignacion) {
            case 'usuario':
                $this->asignarUsuarioEquipo($equipo, $destinoId);
                break;
                
            case 'sucursal':
                $this->asignarSucursalEquipo($equipo, $destinoId);
                break;
                
            case 'ninguno':
                $this->quitarAsignacionEquipo($equipo);
                break;
        }
    }

    private function procesarAsignacionInsumo(Insumo $insumo, $tipoAsignacion, $destinoId, $cantidad = null)
    {
        switch ($tipoAsignacion) {
            case 'usuario':
                $this->asignarUsuarioInsumo($insumo, $destinoId, $cantidad);
                break;
                
            case 'sucursal':
                $this->asignarSucursalInsumo($insumo, $destinoId);
                break;
                
            case 'ninguno':
                $this->quitarAsignacionInsumo($insumo);
                break;
        }
    }

    private function asignarUsuarioEquipo(Equipo $equipo, $usuarioId)
    {
        if (!$usuarioId) return;

        // Cerrar asignación activa anterior
        Asignacion::where('equipo_id', $equipo->id)
            ->whereNull('fecha_fin')
            ->update(['fecha_fin' => now()]);

        // Crear nueva asignación
        Asignacion::create([
            'equipo_id' => $equipo->id,
            'usuario_id' => $usuarioId,
            'fecha_asignacion' => now(),
            'motivo' => 'Asignación masiva desde inventario',
        ]);

        // Cambiar estado a "Asignado"
        $estadoAsignado = EstadoEquipo::where('nombre', 'Asignado')->first();
        if ($estadoAsignado) {
            $equipo->update(['estado_equipo_id' => $estadoAsignado->id]);
        }

        // Registrar movimiento
        $usuarioNombre = Usuario::find($usuarioId)->nombre ?? 'Usuario desconocido';
        $equipo->registrarMovimiento(
            'Asignación de Usuario', 
            "Asignado a: {$usuarioNombre} (masivo)"
        );
    }

    private function asignarSucursalEquipo(Equipo $equipo, $sucursalId)
    {
        if ($sucursalId && $equipo->sucursal_id != $sucursalId) {
            $sucursalAnterior = $equipo->sucursal->nombre ?? 'N/A';
            $sucursalNueva = Sucursal::find($sucursalId)->nombre ?? 'N/A';
            
            $equipo->update(['sucursal_id' => $sucursalId]);
            $equipo->registrarMovimiento('Cambio de Sucursal', "De: $sucursalAnterior a: $sucursalNueva (masivo)");
        }
    }

    private function quitarAsignacionEquipo(Equipo $equipo)
    {
        // Cerrar asignación activa
        Asignacion::where('equipo_id', $equipo->id)
            ->whereNull('fecha_fin')
            ->update([
                'fecha_fin' => now(),
                'motivo' => 'Desasignación masiva desde inventario'
            ]);

        // Cambiar estado a "Disponible"
        $estadoDisponible = EstadoEquipo::where('nombre', 'Disponible')->first();
        if ($estadoDisponible) {
            $equipo->update(['estado_equipo_id' => $estadoDisponible->id]);
        }

        // Registrar movimiento
        $equipo->registrarMovimiento('Desasignación de Usuario', 'Usuario removido (masivo)');
    }

    private function asignarUsuarioInsumo(Insumo $insumo, $usuarioId, $cantidad = null)
    {
        if (!$usuarioId) return;

        // Para insumos, usar la cantidad proporcionada o asignar 1 por defecto
        $cantidadAsignar = $cantidad ?? 1;
        
        // Validar que haya suficiente cantidad
        if ($cantidadAsignar > $insumo->cantidad) {
            throw new \Exception("No hay suficiente cantidad disponible del insumo {$insumo->nombre}. Disponible: {$insumo->cantidad}, Solicitado: {$cantidadAsignar}");
        }

        // Cerrar asignación activa anterior si existe
        AsignacionInsumo::where('insumo_id', $insumo->id)
            ->whereNull('fecha_fin')
            ->update(['fecha_fin' => now()]);

        // Crear nueva asignación en el historial de asignaciones
        AsignacionInsumo::create([
            'insumo_id' => $insumo->id,
            'usuario_id' => $usuarioId,
            'cantidad' => $cantidadAsignar,
            'fecha_asignacion' => now(),
            'motivo' => 'Asignación masiva desde inventario',
        ]);

        // Reducir la cantidad del insumo
        $insumo->decrement('cantidad', $cantidadAsignar);

        // Registrar movimiento
        $usuarioNombre = Usuario::find($usuarioId)->nombre ?? 'Usuario desconocido';
        $insumo->registrarMovimiento(
            'Asignación de Usuario', 
            "Asignado: {$cantidadAsignar} unidades a: {$usuarioNombre} (masivo). Cantidad restante: {$insumo->cantidad}"
        );

        // Si se asignó todo el insumo, cambiar estado a "Asignado"
        if ($insumo->cantidad <= 0) {
            $estadoAsignado = EstadoEquipo::where('nombre', 'Asignado')->first();
            if ($estadoAsignado) {
                $insumo->update(['estado_equipo_id' => $estadoAsignado->id]);
            }
        }
    }

    private function asignarSucursalInsumo(Insumo $insumo, $sucursalId)
    {
        if ($sucursalId && $insumo->sucursal_id != $sucursalId) {
            $sucursalAnterior = $insumo->sucursal->nombre ?? 'N/A';
            $sucursalNueva = Sucursal::find($sucursalId)->nombre ?? 'N/A';
            
            $insumo->update(['sucursal_id' => $sucursalId]);
            $insumo->registrarMovimiento('Cambio de Sucursal', "De: $sucursalAnterior a: $sucursalNueva (masivo)");
        }
    }

    private function quitarAsignacionInsumo(Insumo $insumo)
    {
        // Cerrar asignación activa
        AsignacionInsumo::where('insumo_id', $insumo->id)
            ->whereNull('fecha_fin')
            ->update([
                'fecha_fin' => now(),
                'motivo' => 'Desasignación masiva desde inventario'
            ]);

        // Cambiar estado a "Disponible"
        $estadoDisponible = EstadoEquipo::where('nombre', 'Disponible')->first();
        if ($estadoDisponible) {
            $insumo->update(['estado_equipo_id' => $estadoDisponible->id]);
        }

        // Registrar movimiento
        $insumo->registrarMovimiento('Desasignación de Usuario', 'Usuario removido (masivo)');
    }

    private function generarMensajeExito($tipoAsignacion, $cantidad, $cantidadInsumos = null)
    {
        $cantidadTexto = $cantidadInsumos ? " (Cantidad de insumos: {$cantidadInsumos})" : '';

        $mensajes = [
            'usuario' => "Asignación de usuario aplicada a {$cantidad} elementos correctamente{$cantidadTexto}",
            'sucursal' => "Cambio de sucursal aplicado a {$cantidad} elementos correctamente", 
            'ninguno' => "Desasignación aplicada a {$cantidad} elementos correctamente"
        ];
        
        return $mensajes[$tipoAsignacion] ?? "Operación completada para {$cantidad} elementos";
    }

    public function detalleEquipo($id)
    {
        $equipo = Equipo::with([
            'tipoEquipo', 
            'estadoEquipo', 
            'proveedor', 
            'sucursal',
            'usuarioAsignado.usuario',
            'asignaciones.usuario',
            'movimientos',
            'documentos'
        ])->findOrFail($id);
        
        $usuarios = Usuario::all();
        $estados = EstadoEquipo::all();
        $sucursales = Sucursal::all();
        $proveedores = Proveedor::all();

        return view('detalle_equipo', compact('equipo', 'usuarios', 'estados', 'sucursales', 'proveedores'));
    }

    public function detalleInsumo($id)
    {
        $insumo = Insumo::with([
            'estadoEquipo', 
            'proveedor', 
            'sucursal',
            'usuarioAsignado.usuario',
            'asignaciones.usuario',
            'movimientos',
            'documentos'
        ])->findOrFail($id);
        
        $usuarios = Usuario::all();
        $estados = EstadoEquipo::all();
        $sucursales = Sucursal::all();
        $proveedores = Proveedor::all();

        return view('detalle_insumo', compact('insumo', 'usuarios', 'estados', 'sucursales', 'proveedores'));
    }

    public function limpiarNotificaciones()
    {
        session()->forget('notificaciones_asignaciones');
        return redirect()->route('inventario')->with('success', 'Notificaciones limpiadas');
    }
}