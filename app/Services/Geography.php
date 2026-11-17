<?php

namespace App\Services;

class Geography
{
    private ?array $feature = null;

    public function feature(): array
    {
        return $this->feature ??= json_decode(file_get_contents(config('geography.boundary_file')), true, flags: JSON_THROW_ON_ERROR);
    }

    public function contains(float $lat, float $lng): bool
    {
        if (! is_finite($lat) || ! is_finite($lng) || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return false;
        }
        $geometry = $this->feature()['geometry'];
        $polygons = $geometry['type'] === 'Polygon' ? [$geometry['coordinates']] : $geometry['coordinates'];
        foreach ($polygons as $polygon) {
            if (! $this->ring($lat, $lng, $polygon[0])) {
                continue;
            }
            $hole = false;
            foreach (array_slice($polygon, 1) as $ring) {
                if ($this->ring($lat, $lng, $ring)) {
                    $hole = true;
                }
            }
            if (! $hole) {
                return true;
            }
        }

        return false;
    }

    private function ring(float $y, float $x, array $ring): bool
    {
        $inside = false;
        $n = count($ring);
        for ($i = 0,$j = $n - 1; $i < $n; $j = $i++) {
            [$xi,$yi] = $ring[$i];
            [$xj,$yj] = $ring[$j];
            $cross = ($x - $xi) * ($yj - $yi) - ($y - $yi) * ($xj - $xi);
            if (abs($cross) < 1e-12 && $x >= min($xi, $xj) && $x <= max($xi, $xj) && $y >= min($yi, $yj) && $y <= max($yi, $yj)) {
                return true;
            }
            if (($yi > $y) !== ($yj > $y) && $x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    public function distance(float $a, float $b, float $c, float $d): float
    {
        $h = sin(deg2rad($c - $a) / 2) ** 2 + cos(deg2rad($a)) * cos(deg2rad($c)) * sin(deg2rad($d - $b) / 2) ** 2;

        return 6371000 * 2 * asin(min(1, sqrt($h)));
    }
}
