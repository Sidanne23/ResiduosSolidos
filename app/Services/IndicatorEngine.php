<?php

namespace App\Services;

use Illuminate\Support\Collection;

class IndicatorEngine
{
    public function calculate(Collection $records, Collection $routes): array
    {
        $r = $records->keyBy('code');
        $q = $routes->keyBy('number');
        $value = fn ($n) => $r->get(sprintf('R%02d', $n))?->value;
        $results = [];
        $add = function ($code, $label, $operands, $unit, $source, $period, $formula, $compute, $warning = '', $operator = '=') use (&$results, $value, $r) {
            $inputs = array_map($value, $operands);
            $selected = collect($operands)->map(fn ($n) => $r->get(sprintf('R%02d', $n)))->filter();
            $compatible = $selected->count() === count($operands)
                && $selected->pluck('source_id')->unique()->count() === 1
                && $selected->pluck('period')->unique()->count() === 1
                && $selected->pluck('year')->unique()->count() === 1
                && $selected->pluck('process')->unique()->count() === 1
                && $selected->pluck('territory')->unique()->count() === 1
                && $selected->every(fn ($record) => $record->source->code === $source && $record->operator === ($code === 'IC11' ? '>' : '='));
            $expectedUnits = match ($code) {
                'IC10','IC11' => ['t'], 'IC12' => ['puntos'], 'IC13' => ['vehículos', 'vehículo'],
                'IC14' => ['t/mes', 't/día'], 'IC07' => ['S/', '%'], default => ['S/'],
            };
            $compatible = $compatible && $selected->every(fn ($record) => in_array($record->unit, $expectedUnits, true));
            $v = ! $compatible || in_array(null, $inputs, true) ? null : $compute($inputs);
            $results[$code] = compact('code', 'label', 'unit', 'source', 'period', 'formula', 'warning', 'operator') + ['value' => $v, 'inputs' => array_map(fn ($n) => sprintf('R%02d', $n), $operands)];
        };
        $ratio = fn ($a, $b) => bccomp((string) $b, '0', 30) === 0 ? null : bcdiv((string) $a, (string) $b, 30);
        $total = $routes->count() === 11 ? array_reduce($routes->pluck('participants')->all(), fn ($s, $n) => bcadd($s, (string) $n, 30), '0') : null;
        $results['IC01'] = ['code' => 'IC01', 'label' => 'Registros del cuadro de rutas', 'value' => $total, 'unit' => 'registros', 'source' => 'M24', 'period' => 'Corte parcial 2024', 'formula' => 'Σ registros de las 11 rutas', 'warning' => 'No acredita participantes únicos ni viviendas.', 'operator' => '=', 'inputs' => []];
        $results['IC02'] = ['code' => 'IC02', 'label' => 'Concentración de rutas 1 y 8', 'value' => $total !== null && $q->has(1) && $q->has(8) ? $ratio((string) ($q[1]->participants + $q[8]->participants), $total) : null, 'unit' => '%', 'source' => 'M24', 'period' => 'Corte parcial 2024', 'formula' => '(ruta 1 + ruta 8) / total del cuadro', 'warning' => 'Distribución interna, no cobertura poblacional.', 'operator' => '=', 'inputs' => []];
        $add('IC03', 'Ejecución de recolección y transporte', [9, 8], '%', 'M24', 'Corte parcial 2024', 'R09 / R08', fn ($v) => $ratio(...$v));
        $add('IC04', 'Diferencia de recolección al corte', [8, 9], 'S/', 'M24', 'Corte parcial 2024', 'R08 − R09', fn ($v) => bcsub(...[...$v, 30]), 'Diferencia aritmética; no es ahorro disponible.');
        $add('IC05', 'Ejecución de barrido', [6, 5], '%', 'M24', 'Corte parcial 2024', 'R06 / R05', fn ($v) => $ratio(...$v));
        $add('IC06', 'Ejecución EDUCCA recalculada', [12, 11], '%', 'M24', 'Corte parcial 2024', 'R12 / R11', fn ($v) => $ratio(...$v), 'Publicado R13: 93,14 %. Ambos valores se conservan.');
        $add('IC07', 'Diferencia EDUCCA frente al publicado', [12, 11, 13], 'puntos porcentuales', 'M24', 'Corte parcial 2024', '(R12 / R11 − R13 / 100) × 100', fn ($v) => $ratio($v[0], $v[1]) === null ? null : bcmul(bcsub($ratio($v[0], $v[1]), bcdiv($v[2], '100', 30), 30), '100', 30));
        $add('IC08', 'Ejecución del PP 0036', [22, 21], '%', 'M25', 'Primer semestre 2025', 'R22 / R21', fn ($v) => $ratio(...$v), 'Universo distinto de la actividad de recolección 2024.');
        $add('IC09', 'Diferencia del PP 0036 al corte', [21, 22], 'S/', 'M25', 'Primer semestre 2025', 'R21 − R22', fn ($v) => bcsub(...[...$v, 30]), 'No demuestra disponibilidad legal.');
        $add('IC10', 'Valorización declarada compatible', [17, 18], 't', 'M25', 'Corte publicado 2025, sin desglose mensual', 'R17 + R18', fn ($v) => bcadd(...[...$v, 30]), 'No comparar como tendencia con recolección selectiva de 2026.');
        $add('IC11', 'Cota de recolección selectiva', [35, 36], 't', 'M26', 'Primeros seis meses 2026', 'umbral R35 + umbral R36; propagar >', fn ($v) => $r->get('R35')?->operator === '>' && $r->get('R36')?->operator === '>' ? bcadd(...[...$v, 30]) : null, 'Cota inferior estricta; no es masa exacta ni valorización.', '>');
        $add('IC12', 'Erradicación documentada', [20, 19], '%', 'M25', 'Corte publicado 2025', 'R20 / R19', fn ($v) => $ratio(...$v), 'Estado del conjunto identificado al corte.');
        $add('IC13', 'Composición de flota operativa', [28, 29, 30], 'vehículos', 'M26', 'Primer semestre 2026', 'R28 + R29 + R30', fn ($v) => bcadd(bcadd($v[0], $v[1], 30), $v[2], 30), 'Comparar con R27; no sumar adquisiciones de 2024.');
        $add('IC14', 'Cociente mensual / diario', [34, 33], 'días equivalentes', 'M26', 'Primer semestre 2026', 'R34 / R33', fn ($v) => $ratio(...$v), 'Diagnóstico temporal; no son días efectivamente medidos.');
        $add('IC15', 'Asignación escolar EDUCCA', [40, 39], '%', 'E26', 'Programación anual 2026', 'R40 / R39', fn ($v) => $ratio(...$v), 'Presupuesto aprobado, no ejecutado.');
        $add('IC16', 'Asignación comunitaria EDUCCA', [41, 39], '%', 'E26', 'Programación anual 2026', 'R41 / R39', fn ($v) => $ratio(...$v), 'Presupuesto aprobado, no ejecutado.');

        return $results;
    }

    public static function display(?string $value, string $unit, bool $fraction = false): string
    {
        if ($value === null) {
            return 'No calculable';
        }
        if ($fraction && $unit === '%') {
            $value = bcmul($value, '100', 30);
        }
        $decimals = in_array($unit, ['registros', 'vehículos', 'vehículo', 'rutas', 'puntos', 'participantes', 'actividades']) ? 0 : match ($unit) {
            'puntos porcentuales' => 4,
            't/día' => 3,
            default => 2,
        };

        return number_format((float) $value, $decimals, ',', ' ');
    }
}
