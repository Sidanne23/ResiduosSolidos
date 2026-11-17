@extends('layouts.app')
@section('title','Editar usuario')
@section('description',$user->email)
@section('content')<section class="panel"><form method="post" action="{{ route('users.update',$user) }}" class="stack-form">@csrf @method('PUT') @include('users.fields')<button class="button primary" type="submit">Guardar cambios</button></form></section>@endsection
