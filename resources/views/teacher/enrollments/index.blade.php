@extends('layouts.app')
@section('title', 'Estudiantes · '.$subject->code)
@section('content')
<div class="breadcrumb"><a href="{{ route('dashboard') }}">Inicio</a> / <a href="{{ route('teacher.subjects.show', $subject) }}">{{ $subject->code }}</a> / Estudiantes</div>
<h1>Estudiantes autorizados</h1>
<p class="muted">{{ $subject->label }}. Clave inicial de los estudiantes: <strong>{{ config('portal.student_default_password') ? 'la clave genérica definida en la configuración' : 'su código estudiantil' }}</strong> (al primer ingreso deben cambiarla). Solo los estudiantes de esta lista (con correo {{ '@'.config('portal.institutional_domain') }}) pueden ingresar y ver la asignatura.</p>

@if($requests->isNotEmpty())
<div class="card">
    <h3>Solicitudes de acceso</h3>
    <ul class="list">
        @foreach($requests as $r)
            <li><div><strong>{{ $r->name }}</strong> · {{ $r->email }} · Código {{ $r->student_code }}</div>
                <div class="actions">
                    <form class="inline" method="POST" action="{{ route('teacher.requests.approve', $r) }}">@csrf<button class="btn btn-sm btn-ok">Aprobar</button></form>
                    <form class="inline" method="POST" action="{{ route('teacher.requests.reject', $r) }}">@csrf<button class="btn btn-sm btn-danger">Rechazar</button></form>
                </div></li>
        @endforeach
    </ul>
</div>
@endif

<details class="card" @if($students->isEmpty()) open @endif>
    <summary>Cargar o actualizar lista</summary>
    <p class="small muted">Formato: una fila por estudiante con <strong>código; nombre completo; correo institucional</strong>
        (separador punto y coma, coma o tabulador). Puede exportar la lista del sistema académico a CSV desde Excel o pegarla directamente.
        Los estudiantes que ya estén en la lista no se duplican. <a href="{{ route('teacher.enrollments.template', $subject) }}">Descargar plantilla CSV</a>.</p>
    <form method="POST" action="{{ route('teacher.enrollments.import', $subject) }}" enctype="multipart/form-data">
        @csrf
        <label for="file">Archivo CSV</label>
        <input id="file" type="file" name="file" accept=".csv,.txt">
        <label for="list">…o pegue la lista</label>
        <textarea id="list" name="list" placeholder="0012345678;Ana María Pérez Gómez;aperezg{{ '@'.config('portal.institutional_domain') }}">{{ old('list') }}</textarea>
        <p><button class="btn" type="submit">Procesar lista</button></p>
    </form>
</details>

<div class="page-head">
    <h2 style="margin:0">Lista ({{ $students->count() }})</h2>
    <form method="GET" class="actions"><input type="search" name="q" value="{{ $search }}" placeholder="Buscar nombre, correo o código" style="width:260px"><button class="btn btn-sec">Buscar</button></form>
</div>
<div class="table-wrap">
<table>
    <thead><tr><th>#</th><th>Código</th><th>Nombre</th><th>Correo</th><th>Último ingreso</th><th>Estado</th><th></th></tr></thead>
    <tbody>
    @forelse($students as $i => $s)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $s->student_code }}</td>
            <td>{{ $s->name }}</td>
            <td class="small">{{ $s->email }}</td>
            <td class="small">{{ $s->last_login_at?->diffForHumans() ?? 'Nunca' }}</td>
            <td><span class="badge {{ $s->pivot->is_active ? 'badge-ok' : 'badge-off' }}">{{ $s->pivot->is_active ? 'Habilitado' : 'Deshabilitado' }}</span></td>
            <td class="actions">
                <form class="inline" method="POST" action="{{ route('teacher.enrollments.toggle', [$subject, $s->pivot->id]) }}">@csrf @method('PATCH')
                    <button class="btn btn-sm btn-sec">{{ $s->pivot->is_active ? 'Deshabilitar' : 'Habilitar' }}</button></form>
                <form class="inline" method="POST" action="{{ route('teacher.enrollments.reset', [$subject, $s->pivot->id]) }}" onsubmit="return confirm('¿Restablecer la clave de {{ addslashes($s->name) }} a la clave inicial?')">@csrf
                    <button class="btn btn-sm btn-sec" title="Vuelve a la clave inicial">Restablecer clave</button></form>
                <form class="inline" method="POST" action="{{ route('teacher.enrollments.destroy', [$subject, $s->pivot->id]) }}" onsubmit="return confirm('¿Retirar a {{ addslashes($s->name) }} de la lista?')">@csrf @method('DELETE')
                    <button class="btn btn-sm btn-danger">Retirar</button></form>
            </td>
        </tr>
    @empty
        <tr><td colspan="7" class="empty">No hay estudiantes en la lista.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
@endsection
