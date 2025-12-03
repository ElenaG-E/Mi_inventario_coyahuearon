<?php

use Carbon\Carbon;

if (!function_exists('formatearDuracionGarantia')) {
    function formatearDuracionGarantia(Carbon $fechaCompra, ?int $duracionMeses): string
    {
        $duracionMeses = $duracionMeses ?? 0;

        // Sin garantía definida
        if ($duracionMeses <= 0) {
            return 'Sin garantía';
        }

        $vence = $fechaCompra->copy()->addMonths($duracionMeses);
        $diff = now()->diff($vence);

        // Si ya venció
        if ($diff->invert === 1) {
            $diasVencidos = abs($diff->days);
            if ($diasVencidos == 1) {
                return 'Vencida hace 1 día';
            } elseif ($diasVencidos < 7) {
                return "Vencida hace {$diasVencidos} días";
            } elseif ($diasVencidos < 30) {
                $semanas = intdiv($diasVencidos, 7);
                return "Vencida hace {$semanas} " . ($semanas == 1 ? 'semana' : 'semanas');
            } else {
                $meses = intdiv($diasVencidos, 30);
                return "Vencida hace {$meses} " . ($meses == 1 ? 'mes' : 'meses');
            }
        }

        // Si vence hoy
        if ($diff->days === 0) {
            return 'Vence hoy';
        }

        // Tiempo restante
        $partes = [];
        
        if ($diff->y > 0) {
            $partes[] = $diff->y . ' ' . ($diff->y == 1 ? 'año' : 'años');
        }
        
        if ($diff->m > 0) {
            $partes[] = $diff->m . ' ' . ($diff->m == 1 ? 'mes' : 'meses');
        }
        
        // Para días, mostrar semanas y días
        if ($diff->d > 0) {
            $semanas = intdiv($diff->d, 7);
            $dias = $diff->d % 7;
            
            if ($semanas > 0) {
                $partes[] = $semanas . ' ' . ($semanas == 1 ? 'semana' : 'semanas');
            }
            if ($dias > 0) {
                $partes[] = $dias . ' ' . ($dias == 1 ? 'día' : 'días');
            }
        }

        if (empty($partes)) {
            return 'Menos de 1 día';
        }

        return implode(', ', $partes);
    }
}