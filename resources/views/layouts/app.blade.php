<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><link rel="icon" type="image/png" href="{{ asset('images/municipalidad-amarilis.png') }}"><title>@yield('title', 'Panel') · Amarilis Ambiental</title>@vite(['resources/css/app.css', 'resources/js/app.js'])</head>
<body>
<button class="mobile-menu" type="button" data-menu aria-label="Abrir navegación" aria-expanded="false">☰ Menú</button>
<aside class="sidebar" id="navigation">
    <a href="{{ route('operations.dashboard') }}" class="brand"><img class="municipal-logo" src="{{ asset('images/municipalidad-amarilis.png') }}" alt="Logo de la Municipalidad Distrital de Amarilis" width="64" height="64"><span>Amarilis<span class="brand-caption">MUNICIPALIDAD DISTRITAL</span></span></a>
    <div class="sidebar-label">GESTIÓN MUNICIPAL</div>
    <nav aria-label="Navegación principal">
    <a class="nav-link {{ request()->routeIs('operations.*')?'selected':'' }}" href="{{ route('operations.dashboard') }}"><span aria-hidden="true">◈</span>Centro de control</a>
    <a class="nav-link {{ request()->routeIs('incidents.*')&&!request()->routeIs('incidents.map*')?'selected':'' }}" href="{{ route('incidents.index') }}"><span aria-hidden="true">◎</span>Incidencias ambientales</a>
    <a class="nav-link {{ request()->routeIs('incidents.map*')?'selected':'' }}" href="{{ route('incidents.map') }}"><span aria-hidden="true">⌖</span>Mapa de calor</a>
    <a class="nav-link {{ request()->routeIs('sites.*')?'selected':'' }}" href="{{ route('sites.index') }}"><span aria-hidden="true">◇</span>Puntos críticos</a>
    <div class="sidebar-label">ADMINISTRACIÓN</div>
    @can('administer')<a class="nav-link {{ request()->routeIs('users.*') ? 'selected' : '' }}" href="{{ route('users.index') }}"><span aria-hidden="true">◎</span>Usuarios y acceso</a><a class="nav-link {{ request()->routeIs('audit') ? 'selected' : '' }}" href="{{ route('audit') }}"><span aria-hidden="true">↺</span>Auditoría</a>@endcan
    @can('administer')<a class="nav-link {{ request()->routeIs('incident-catalogs.*')?'selected':'' }}" href="{{ route('incident-catalogs.index') }}"><span aria-hidden="true">⚙</span>Configurar incidencias</a>@endcan
    </nav>
    <div class="sidebar-bottom"><div class="corpus-marker"></div><strong>Atención ambiental municipal</strong><p>Registro, ubicación y seguimiento de incidencias.</p><a href="{{ route('profile') }}" class="account"><span class="avatar">{{ mb_substr(auth()->user()->name,0,1) }}</span><span>{{ auth()->user()->name }}<small>{{ ucfirst(auth()->user()->role) }}</small></span><span>↗</span></a><form method="post" action="{{ route('logout') }}">@csrf<button class="logout" type="submit">Cerrar sesión</button></form></div>
</aside>
<div class="workspace">
    <header class="topbar"><span>Gestión de residuos sólidos <span class="topbar-divider">/</span> <b>Amarilis, Huánuco</b></span><span class="scope-badge"><i></i> Gestión operativa</span></header>
    <main id="main-content">
        <div class="page-heading"><div><p class="eyebrow">@yield('eyebrow', 'INFORMACIÓN AMBIENTAL')</p><h1>@yield('title', 'Panel de inteligencia')</h1><p class="page-description">@yield('description', 'Consulta la evidencia disponible y sus condiciones de interpretación.')</p></div><div class="heading-actions">@yield('actions')</div></div>
        @if(session('status'))<div class="notice success" role="status">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="notice danger" role="alert"><strong>Revise la información</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @yield('content')
        <footer class="page-footer"><span>Amarilis · Gestión ambiental municipal</span><span>Registro · Verificación · Atención</span></footer>
    </main>
</div>
</body></html>
