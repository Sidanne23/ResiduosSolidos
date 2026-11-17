@extends('layouts.auth')
@section('title', 'Recuperar acceso')
@section('description', 'Solicita un enlace para restablecer tu contraseña.')
@section('content')
@if(config('mail.default') === 'log')<div class="notice">Entorno local: el correo se registra en el log de Laravel. El envío a una bandeja de correo requiere configurar SMTP.</div>@endif
<form method="post" action="{{ route('password.email') }}" class="stack-form">@csrf<label for="email">Correo electrónico</label><input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required><button class="button primary" type="submit">Solicitar enlace</button></form><a class="text-link" href="{{ route('login') }}">← Volver al inicio de sesión</a>
@endsection
