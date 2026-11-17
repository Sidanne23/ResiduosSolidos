<form method="get" class="filter-panel" aria-label="Filtros de evidencia">
    @if(isset($view))<input type="hidden" name="view" value="{{ $view }}">@endif
    <div class="filter-grid">
    <label>Fuente<select name="source"><option value="">Todas las fuentes</option>@foreach(['M24','M25','M26','E26','DA26','C25','C26'] as $s)<option value="{{ $s }}" @selected(($filters['source'] ?? '') === $s)>{{ $s }}</option>@endforeach</select></label>
    <label>Año declarado<select name="year"><option value="">Todos los años</option>@foreach([2024,2025,2026] as $y)<option value="{{ $y }}" @selected((string)($filters['year'] ?? '') === (string)$y)>{{ $y }}</option>@endforeach</select></label>
    <label>Proceso<select name="process"><option value="">Todos los procesos</option>@foreach(['Recolección y transporte','Barrido y limpieza','Segregación','Recolección selectiva','Valorización','Puntos críticos','Educación ambiental','Programa presupuestal 0036','Recursos operativos'] as $p)<option @selected(($filters['process'] ?? '') === $p)>{{ $p }}</option>@endforeach</select></label>
    <label>Tipo de residuo<select name="waste_type"><option value="">Todos los tipos</option>@foreach(['Orgánico','Inorgánico'] as $w)<option @selected(($filters['waste_type'] ?? '') === $w)>{{ $w }}</option>@endforeach</select></label>
    <button class="button primary" type="submit">Aplicar filtros</button>
    </div>
    <details class="advanced-filters" @if(!empty($filters['period']) || !empty($filters['q'])) open @endif><summary>Corte documental y búsqueda</summary><div class="filter-grid"><label class="grow">Período exacto<select name="period"><option value="">Todos los cortes</option>@foreach(\App\Models\DocumentaryRecord::distinct()->pluck('period') as $period)<option value="{{ $period }}" @selected(($filters['period'] ?? '') === $period)>{{ $period }}</option>@endforeach</select></label><label>Buscar indicador<input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Código o concepto"></label></div></details>
    <div class="filter-caption"><span>Ámbito: Amarilis distrital. El detalle por zona está disponible en el cuadro de rutas.</span><a href="{{ url()->current() }}{{ isset($view) ? '?view='.$view : '' }}">Limpiar filtros ↺</a></div>
</form>
