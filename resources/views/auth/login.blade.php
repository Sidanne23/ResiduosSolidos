@extends('layouts.auth')
@section('title', 'Bienvenido de nuevo')
@section('description', 'Ingresa con tu cuenta para consultar la información.')
@section('content')
<form method="post" action="{{ route('login') }}" class="stack-form">@csrf
<label for="email">Correo electrónico</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="tu@correo.pe">
<div class="label-row"><label for="password">Contraseña</label><a href="{{ route('password.request') }}">¿La olvidaste?</a></div><input id="password" name="password" type="password" required autocomplete="current-password">
<label class="check-label"><input type="checkbox" name="remember"> Mantener mi sesión</label><button class="button primary wide" type="submit">Ingresar a la plataforma <span>→</span></button>
</form><p class="setup-hint">¿Primera instalación? El responsable debe crear una cuenta con el comando <code>app:admin</code>.</p>
@endsection
