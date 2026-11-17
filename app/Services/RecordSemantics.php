<?php

namespace App\Services;

class RecordSemantics
{
    public static function forCode(string $code): array
    {
        $n = (int) substr($code, 1);
        $process = match (true) {
            in_array($n, [5, 6, 7, 31, 32]) => 'Barrido y limpieza',
            in_array($n, [4]) => 'Segregación',
            in_array($n, [11, 12, 13, 24, 25, 39, 40, 41]) => 'Educación ambiental',
            in_array($n, [17, 18]) => 'Valorización',
            in_array($n, [19, 20]) => 'Puntos críticos',
            in_array($n, [21, 22, 23]) => 'Programa presupuestal 0036',
            in_array($n, [35, 36, 37, 38]) => 'Recolección selectiva',
            in_array($n, [3, 27, 28, 29, 30]) => 'Recursos operativos',
            default => 'Recolección y transporte',
        };
        $restrictions = match (true) {
            in_array($n, [1, 14, 16]) => ['Sin denominador verificable; no recalcular cobertura ni derivar tasas.'],
            $n === 15 => ['Período ambiguo; no anualizar ni usar como denominador.'],
            $n === 13 => ['Publicado: 93,14 %; calculado con importes: 93,16 %.'],
            in_array($n, [24, 25]) => ['Participantes declarados no conciliados; unicidad no acreditada.'],
            in_array($n, [33, 34]) => ['Pendiente de conciliación temporal; no extrapolar al año.'],
            in_array($n, [35, 36]) => ['Cota inferior estricta; no es masa exacta ni valorización.'],
            $n >= 39 => ['Programación aprobada; no es gasto ejecutado ni presupuesto BI.'],
            default => [],
        };

        return [
            'process' => $process,
            'waste_type' => match ($n) {
                17, 35, 37 => 'Orgánico', 4, 18, 36, 38 => 'Inorgánico', default => null
            },
            'temporal_precision' => match (true) {
                $n <= 13 => 'corte parcial', $n === 15 => 'ambiguo', $n >= 39 => 'programación anual', $n >= 26 => 'semestre', in_array($n, [21, 22, 23]) => 'semestre', in_array($n, [24, 25]) => 'mayo–agosto', default => 'corte publicado'
            },
            'qualifier' => in_array($n, [32, 34]) ? 'Aproximado' : null,
            'restrictions' => $restrictions,
        ];
    }

    public const ISSUE_RECORDS = [
        'Q01' => ['R01', 'R02', 'R03'], 'Q02' => ['R11', 'R12', 'R13'],
        'Q03' => ['R15'], 'Q04' => ['R16'], 'Q05' => ['R24', 'R25'],
        'Q06' => ['R33', 'R34'], 'Q07' => ['R35', 'R36'], 'Q08' => [],
        'Q09' => ['R26', 'R27', 'R28', 'R29', 'R30', 'R31', 'R32', 'R33', 'R34', 'R35', 'R36', 'R37', 'R38'],
    ];
}
