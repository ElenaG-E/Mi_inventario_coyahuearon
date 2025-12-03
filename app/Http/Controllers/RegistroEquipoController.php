<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\EspecificacionTecnica;
use App\Models\TipoEquipo;
use App\Models\Proveedor;
use App\Models\EstadoEquipo;
use App\Models\Documento;
use App\Models\Insumo;
use App\Models\Sucursal;
use App\Models\Usuario;
use App\Models\Asignacion;
use App\Models\AsignacionInsumo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RegistroEquipoController extends Controller
{
    // Autocompletar nombres de insumos
    public function autocompleteInsumos(Request $request)
    {
        $termino = $request->get('term');

        $resultados = Insumo::where('nombre', 'LIKE', '%' . $termino . '%')
            ->select('nombre')
            ->distinct()
            ->take(10)
            ->get()
            ->pluck('nombre');

        return response()->json($resultados);
    }

    // Formulario de creación
    public function create()
    {
        $tipos              = TipoEquipo::all();
        $proveedores        = Proveedor::all();
        $estados            = EstadoEquipo::all();
        $facturas           = Documento::where('tipo', 'factura')->get();
        $garantias          = Documento::where('tipo', 'garantia')->get();
        $insumosExistentes  = Insumo::select('nombre')->distinct()->get();
        $sucursales         = Sucursal::all();
        $usuarios           = Usuario::all();

        return view('registro_equipo', compact(
            'tipos',
            'proveedores',
            'estados',
            'facturas',
            'garantias',
            'insumosExistentes',
            'sucursales',
            'usuarios'
        ));
    }

    // Eliminar equipo o insumo
    public function destroy(Request $request, $id)
    {
        $request->validate([
            'password_confirm' => 'required|string',
        ]);

        if (!Hash::check($request->password_confirm, auth()->user()->password)) {
            return redirect()->route('inventario')
                ->withErrors(['password_confirm' => 'La contraseña ingresada no es correcta']);
        }

        // Determinar si es equipo o insumo
        $equipo = Equipo::find($id);
        if ($equipo) {
            $equipo->delete();
            return redirect()->route('inventario')->with('success', 'Equipo eliminado correctamente.');
        }

        $insumo = Insumo::find($id);
        if ($insumo) {
            $insumo->delete();
            return redirect()->route('inventario')->with('success', 'Insumo eliminado correctamente.');
        }

        return redirect()->route('inventario')->with('error', 'No se encontró el elemento a eliminar.');
    }

    // Actualizar equipo o insumo
    public function update(Request $request, $id)
    {
        if ($request->categoria === 'insumo') {
            return $this->updateInsumo($request, $id);
        } else {
            return $this->updateEquipo($request, $id);
        }
    }

    private function updateInsumo(Request $request, $id)
    {
        $insumo = Insumo::findOrFail($id);
        $oldSucursal = $insumo->sucursal_id;

        $request->validate([
            'nombre'            => 'nullable|string|max:255',
            'cantidad'          => 'nullable|integer|min:0',
            'estado_equipo_id'  => 'nullable|exists:estados_equipo,id',
            'proveedor_id'      => 'nullable|exists:proveedores,id',
            'sucursal_id'       => 'nullable|exists:sucursales,id',
            'precio'            => 'nullable|numeric|min:0',
            'usuario_id'        => 'nullable|exists:usuarios,id',
            'cantidad_asignada' => 'nullable|integer|min:1',
            'motivo'            => 'nullable|string|max:255',
        ]);

        $insumo->update($request->only([
            'nombre',
            'cantidad',
            'estado_equipo_id',
            'proveedor_id',
            'sucursal_id',
            'precio',
        ]));

        if ($request->filled('sucursal_id') && $oldSucursal != $request->sucursal_id) {
            $sucursalNombre = Sucursal::find($request->sucursal_id)?->nombre ?? 'N/A';
            $insumo->registrarMovimiento('Cambio de Sucursal', 'Sucursal cambiada a: ' . $sucursalNombre);
        }

        if ($request->filled('usuario_id')) {
            AsignacionInsumo::where('insumo_id', $insumo->id)->whereNull('fecha_fin')->update(['fecha_fin' => now()]);

            AsignacionInsumo::create([
                'insumo_id'        => $insumo->id,
                'usuario_id'       => $request->usuario_id,
                'cantidad'         => $request->cantidad_asignada ?? 1,
                'fecha_asignacion' => now(),
                'motivo'           => $request->motivo ?? 'Cambio de Usuario',
            ]);
        } else {
            AsignacionInsumo::where('insumo_id', $insumo->id)
                ->whereNull('fecha_fin')
                ->update([
                    'fecha_fin' => now(),
                    'motivo'    => $request->motivo ?? 'Desasignación de usuario'
                ]);
        }

        return redirect()->route('inventario.insumo', $insumo->id)
                         ->with('success', 'Insumo actualizado correctamente.');
    }

    private function updateEquipo(Request $request, $id)
    {
        $equipo = Equipo::findOrFail($id);
        $oldSucursal = $equipo->sucursal_id;

        $request->validate([
            'marca'            => 'nullable|string|max:100',
            'modelo'           => 'nullable|string|max:100',
            'numero_serie'     => 'nullable|string|max:100|unique:equipos,numero_serie,' . $equipo->id,
            'estado_equipo_id' => 'nullable|exists:estados_equipo,id',
            'proveedor_id'     => 'nullable|exists:proveedores,id',
            'sucursal_id'      => 'nullable|exists:sucursales,id',
            'precio'           => 'nullable|numeric|min:0',
            'usuario_id'       => 'nullable|exists:usuarios,id',
            'motivo'           => 'nullable|string|max:255',
        ]);

        $equipo->update($request->only([
            'marca',
            'modelo',
            'numero_serie',
            'estado_equipo_id',
            'proveedor_id',
            'sucursal_id',
            'precio',
        ]));

        if ($request->filled('sucursal_id') && $oldSucursal != $request->sucursal_id) {
            $sucursalNombre = Sucursal::find($request->sucursal_id)?->nombre ?? 'N/A';
            $equipo->registrarMovimiento('Cambio de Sucursal', 'Sucursal cambiada a: ' . $sucursalNombre);
        }

        if ($request->estado_equipo_id == 4) {
            $equipo->registrarMovimiento('Baja', 'Equipo dado de baja');
        }

        if ($request->filled('usuario_id')) {
            Asignacion::where('equipo_id', $equipo->id)->whereNull('fecha_fin')->update(['fecha_fin' => now()]);

            Asignacion::create([
                'equipo_id'        => $equipo->id,
                'usuario_id'       => $request->usuario_id,
                'fecha_asignacion' => now(),
                'motivo'           => $request->motivo ?? 'Cambio de Usuario',
            ]);
        } else {
            Asignacion::where('equipo_id', $equipo->id)
                ->whereNull('fecha_fin')
                ->update([
                    'fecha_fin' => now(),
                    'motivo'    => $request->motivo ?? 'Desasignación de usuario'
                ]);
        }

        return redirect()->route('inventario.equipo', $equipo->id)
                         ->with('success', 'Equipo actualizado correctamente.');
    }

    // Store para equipos
    public function store(Request $request)
    {
        return $this->storeEquipo($request);
    }

    // Store para insumos
    public function storeInsumo(Request $request)
    {
        return $this->storeInsumoData($request);
    }

    private function storeEquipo(Request $request)
    {
        $request->validate([
            'tipo_equipo_id'   => 'required|exists:tipos_equipo,id',
            'proveedor_id'     => 'nullable|exists:proveedores,id',
            'estado_equipo_id' => 'nullable|exists:estados_equipo,id',
            'sucursal_id'      => 'nullable|exists:sucursales,id',
            'marca'            => 'nullable|string|max:100',
            'modelo'           => 'nullable|string|max:100',
            'numero_serie'     => 'nullable|string|max:100|unique:equipos,numero_serie',
            'precio'           => 'nullable|numeric|min:0',
            'fecha_compra'     => 'nullable|date',
            'fecha_registro'   => 'nullable|date',
            'usuario_id'       => 'nullable|exists:usuarios,id',
            'motivo'           => 'nullable|string|max:255',
        ]);

        $estadoDisponibleId = EstadoEquipo::where('nombre','Disponible')->first()->id ?? 1;

        $equipo = Equipo::create([
            'tipo_equipo_id'   => $request->tipo_equipo_id,
            'proveedor_id'     => $request->proveedor_id,
            'estado_equipo_id' => $request->estado_equipo_id ?? $estadoDisponibleId,
            'sucursal_id'      => $request->sucursal_id,
            'marca'            => $request->marca,
            'modelo'           => $request->modelo,
            'numero_serie'     => $request->numero_serie,
            'precio'           => $request->precio,
            'fecha_compra'     => $request->fecha_compra,
            'fecha_registro'   => $request->fecha_registro ?? now(),
            'qr_code'          => $request->numero_serie ?? ('EQ-' . now()->format('YmdHis')),
        ]);

        $equipo->registrarMovimiento('Registro de Equipo', 'Equipo creado en el sistema');

        if ($request->filled('usuario_id')) {
            Asignacion::create([
                'equipo_id'        => $equipo->id,
                'usuario_id'       => $request->usuario_id,
                'fecha_asignacion' => now(),
                'motivo'           => $request->motivo ?? 'Asignación inicial',
            ]);
        }

        // Crear especificaciones técnicas si hay datos
        $especificacionesData = $request->only((new EspecificacionTecnica)->getFillable());
        if (!empty(array_filter($especificacionesData))) {
            EspecificacionTecnica::create(array_merge(
                ['equipo_id' => $equipo->id],
                $especificacionesData
            ));
        }

        // Procesar documentos subidos
        $this->procesarDocumentos($request, $equipo);

        return redirect()->route('registro_equipo.create')
                         ->with('success', 'Equipo registrado correctamente.')
                         ->with('qr_generado', true);
    }

    private function storeInsumoData(Request $request)
    {
        $request->validate([
            'nombre_insumo'    => 'required|string|max:255',
            'cantidad'         => 'required|integer|min:1',
            'estado_equipo_id' => 'nullable|exists:estados_equipo,id',
            'proveedor_id'     => 'nullable|exists:proveedores,id',
            'sucursal_id'      => 'nullable|exists:sucursales,id',
            'precio'           => 'nullable|numeric|min:0',
            'fecha_compra'     => 'nullable|date',
            'fecha_registro'   => 'nullable|date',
            'usuario_id'       => 'nullable|exists:usuarios,id',
            'cantidad_asignada'=> 'nullable|integer|min:1',
            'motivo'           => 'nullable|string|max:255',
        ]);

        $estadoDisponibleId = EstadoEquipo::where('nombre','Disponible')->first()->id ?? 1;

        $insumo = Insumo::create([
            'nombre'           => $request->nombre_insumo,
            'cantidad'         => $request->cantidad,
            'estado_equipo_id' => $request->estado_equipo_id ?? $estadoDisponibleId,
            'proveedor_id'     => $request->proveedor_id,
            'sucursal_id'      => $request->sucursal_id,
            'precio'           => $request->precio,
            'fecha_compra'     => $request->fecha_compra,
            'fecha_registro'   => $request->fecha_registro ?? now(),
        ]);

        $insumo->registrarMovimiento('Registro de Insumo', 'Insumo creado en el sistema');

        if ($request->filled('usuario_id')) {
            AsignacionInsumo::create([
                'insumo_id'        => $insumo->id,
                'usuario_id'       => $request->usuario_id,
                'cantidad'         => $request->cantidad_asignada ?? 1,
                'fecha_asignacion' => now(),
                'motivo'           => $request->motivo ?? 'Asignación inicial',
            ]);
        }

        // Procesar documentos subidos
        $this->procesarDocumentos($request, $insumo, 'insumo');

        return redirect()->route('registro_equipo.create')
                         ->with('success', 'Insumo registrado correctamente.');
    }

    private function procesarDocumentos(Request $request, $model, $tipo = 'equipo')
    {
        // Procesar facturas subidas
        if ($request->hasFile('documentos_factura')) {
            foreach ($request->file('documentos_factura') as $archivo) {
                if ($archivo->isValid()) {
                    $nombreArchivo = $archivo->getClientOriginalName();
                    $ruta = $archivo->store('documentos');
                    
                    Documento::create([
                        'nombre_archivo' => $nombreArchivo,
                        'ruta_archivo' => $ruta,
                        'tipo' => 'factura',
                        'documentable_id' => $model->id,
                        'documentable_type' => $tipo === 'equipo' ? Equipo::class : Insumo::class,
                    ]);
                }
            }
        }

        // Procesar garantías subidas
        if ($request->hasFile('documentos_garantia')) {
            foreach ($request->file('documentos_garantia') as $archivo) {
                if ($archivo->isValid()) {
                    $nombreArchivo = $archivo->getClientOriginalName();
                    $ruta = $archivo->store('documentos');
                    
                    Documento::create([
                        'nombre_archivo' => $nombreArchivo,
                        'ruta_archivo' => $ruta,
                        'tipo' => 'garantia',
                        'tiempo_garantia_meses' => $request->tiempo_garantia_meses,
                        'documentable_id' => $model->id,
                        'documentable_type' => $tipo === 'equipo' ? Equipo::class : Insumo::class,
                    ]);
                }
            }
        }

        // Asociar documentos existentes
        if ($request->filled('factura_ids')) {
            Documento::whereIn('id', $request->factura_ids)
                ->update([
                    'documentable_id' => $model->id,
                    'documentable_type' => $tipo === 'equipo' ? Equipo::class : Insumo::class,
                ]);
        }

        if ($request->filled('garantia_ids')) {
            Documento::whereIn('id', $request->garantia_ids)
                ->update([
                    'documentable_id' => $model->id,
                    'documentable_type' => $tipo === 'equipo' ? Equipo::class : Insumo::class,
                    'tiempo_garantia_meses' => $request->tiempo_garantia_meses,
                ]);
        }
    }
}