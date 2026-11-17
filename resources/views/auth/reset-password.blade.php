@extends('layouts.auth')
@section('title', 'Nueva contraseña')
@section('description', 'Usa al menos 12 caracteres para proteger tu cuenta.')
@section('content')
<form method="post" action="{{ route('password.update') }}" class="stack-form">@csrf<input type="hidden" name="token" value="{{ $request->route('token') }}"><label for="email">Correo</label><input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autocomplete="username"><label for="password">Contraseña nueva</label><input id="password" type="password" name="password" minlength="12" required autocomplete="new-password"><label for="password_confirmation">Repetir contraseña</label><input id="password_confirmation" type="password" name="password_confirmation" minlength="12" required autocomplete="new-password"><button class="button primary" type="submit">Guardar contraseña</button></form>
@endsection
