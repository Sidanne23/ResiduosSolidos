<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Services\Geography;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;

class GeographyController extends Controller
{
    public function search(Request $request, Geography $geo)
    {
        Gate::authorize('create', Incident::class);
        $data = $request->validate(['q' => ['required', 'string', 'min:3', 'max:180']]);
        $key = 'geocoder:'.hash('sha256', mb_strtolower($data['q']));
        if (Cache::has($key)) {
            return response()->json(Cache::get($key));
        }
        $lock = Cache::lock('geocoder:global', 10);
        abort_unless($lock->get(), 429, 'Búsqueda ocupada. Espere un momento.');
        try {
            abort_if(microtime(true) - (float) Cache::get('geocoder:last', 0) < 1.1, 429, 'Espere al menos un segundo antes de otra búsqueda.');
            Cache::put('geocoder:last', microtime(true), 60);
            $box = $geo->feature()['properties']['bbox'];
            $response = Http::withHeaders(['User-Agent' => config('geography.geocoder_agent'), 'Accept-Language' => 'es'])->timeout(12)->get(config('geography.geocoder_url'), [
                'q' => $data['q'].', Amarilis, Huánuco, Perú', 'countrycodes' => 'pe', 'format' => 'jsonv2', 'limit' => 5, 'bounded' => 1, 'viewbox' => implode(',', [$box[2], $box[1], $box[3], $box[0]])]);
            if (! $response->successful()) {
                return response()->json(['message' => 'El buscador externo no está disponible. Puede seleccionar el punto en el mapa o introducir coordenadas reales.'], 503);
            }
            $results = collect($response->json())->filter(fn ($r) => isset($r['lat'],$r['lon']) && $geo->contains((float) $r['lat'], (float) $r['lon']))->map(fn ($r) => ['label' => $r['display_name'], 'latitude' => (float) $r['lat'], 'longitude' => (float) $r['lon']])->values()->all();
            $payload = ['results' => $results, 'attribution' => '© OpenStreetMap contributors · Nominatim'];
            Cache::put($key, $payload, now()->addDay());

            return response()->json($payload);
        } catch (ConnectionException $e) {
            return response()->json(['message' => 'No se pudo conectar con el buscador. La selección manual del mapa sigue disponible.'], 503);
        } finally {
            $lock->release();
        }
    }
}
