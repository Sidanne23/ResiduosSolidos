@extends('layouts.app')
@section('title','Usuarios y acceso')
@section('description','Perfiles técnicos propuestos para el sistema; no representan cargos municipales acreditados.')
@section('content')
<section class="panel"><h2>Cuentas del sistema</h2><div class="table-wrap"><table><thead><tr><th>Nombre y correo</th><th>Rol</th><th>Estado</th><th></th></tr></thead><tbody>@foreach($users as $account)<tr><td><strong>{{ $account->name }}</strong><small>{{ $account->email }}</small></td><td>{{ ucfirst($account->role) }}</td><td><span class="badge {{ $account->active ? 'published' : 'warning' }}">{{ $account->active ? 'Activo' : 'Suspendido' }}</span></td><td><a class="text-link" href="{{ route('users.edit',$account) }}">Editar ↗</a></td></tr>@endforeach</tbody></table></div><div class="pagination">{{ $users->links() }}</div></section>
<section class="panel"><h2>Crear usuario</h2><form method="post" action="{{ route('users.store') }}" class="stack-form">@csrf @include('users.fields')<button class="button primary" type="submit">Crear cuenta</button></form></section>
@endsection
