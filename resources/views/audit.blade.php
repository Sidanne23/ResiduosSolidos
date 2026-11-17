@extends('layouts.app')
@section('title','Auditoría')
@section('description','Registro de accesos, importaciones, reportes y cambios de cuentas.')
@section('content')<section class="panel"><div class="table-wrap"><table><thead><tr><th>Fecha UTC</th><th>Actor</th><th>Acción</th><th>Entidad</th><th>Detalle seguro</th></tr></thead><tbody>@forelse($events as $event)<tr><td>{{ $event->created_at }}</td><td>{{ $event->email ?? 'Comando local' }}</td><td>{{ $event->action }}</td><td>{{ $event->subject }}</td><td>{{ $event->detail }}</td></tr>@empty<tr><td colspan="5">Sin acciones registradas.</td></tr>@endforelse</tbody></table></div><div class="pagination">{{ $events->links() }}</div></section>@endsection
