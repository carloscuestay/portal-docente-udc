@extends('layouts.app')
@section('title', $subject->name)
@section('content')
<div class="breadcrumb"><a href="{{ route('dashboard') }}">Inicio</a> / {{ $subject->code }}</div>
<div class="page-head">
    <div>
        <h1>{{ $subject->name }}</h1>
        <div class="muted">{{ $subject->program }} · {{ $subject->code }}{{ $subject->group ? ' · Grupo '.$subject->group : '' }} · {{ $subject->period }}
            <span class="badge {{ $subject->is_active ? 'badge-ok' : 'badge-off' }}">{{ $subject->is_active ? 'Activa' : 'Inactiva' }}</span></div>
    </div>
    <div class="actions">
        <a class="btn btn-sec" href="{{ route('teacher.enrollments.index', $subject) }}">Estudiantes ({{ $subject->active_students_count }})</a>
        <a class="btn btn-sec" href="{{ route('teacher.subjects.edit', $subject) }}">Editar</a>
    </div>
</div>

@if($requests->isNotEmpty())
    <div class="alert alert-warn">Hay {{ $requests->count() }} solicitud(es) de acceso pendientes.
        <a href="{{ route('teacher.enrollments.index', $subject) }}">Revisar</a></div>
@endif
@if($subject->active_students_count === 0)
    <div class="alert alert-warn">Esta asignatura no tiene estudiantes autorizados.
        <a href="{{ route('teacher.enrollments.index', $subject) }}">Cargue la lista</a> para que puedan ingresar.</div>
@endif
@if($subject->description)<div class="card prose">{{ $subject->description }}</div>@endif

<div class="page-head" style="margin-top:28px">
    <h2 style="margin:0">Evaluaciones</h2>
    <a class="btn" href="{{ route('teacher.quizzes.create', $subject) }}">+ Nueva evaluación</a>
</div>
<div class="table-wrap">
<table>
    <thead><tr><th>Evaluación</th><th>Estado</th><th>Ventana</th><th>Preguntas</th><th>Intentos</th><th></th></tr></thead>
    <tbody>
    @forelse($quizzes as $q)
        @php $st = $q->statusLabel(); @endphp
        <tr>
            <td><a href="{{ route('teacher.quizzes.show', $q) }}"><strong>{{ $q->title }}</strong></a></td>
            <td><span class="badge {{ ['Abierta' => 'badge-ok', 'Programada' => 'badge-warn', 'Borrador' => 'badge-off', 'Cerrada' => 'badge-err'][$st] }}">{{ $st }}</span></td>
            <td class="small">{{ $q->opens_at?->format('d/m/Y H:i') ?? 'Sin inicio' }}<br>{{ $q->closes_at?->format('d/m/Y H:i') ?? 'Sin cierre' }}</td>
            <td>{{ $q->questions_count }}</td>
            <td>{{ $q->attempts_count }}</td>
            <td><a class="btn btn-sm btn-sec" href="{{ route('teacher.quizzes.results', $q) }}">Resultados</a></td>
        </tr>
    @empty
        <tr><td colspan="6" class="empty">Sin evaluaciones.</td></tr>
    @endforelse
    </tbody>
</table>
</div>

<div class="page-head" style="margin-top:28px">
    <h2 style="margin:0">Material de clase</h2>
    <a class="btn" href="{{ route('teacher.materials.create', $subject) }}">+ Agregar material</a>
</div>
@forelse($materials as $unit => $items)
    <div class="card">
        <h3>{{ $unit }}</h3>
        <ul class="list">
            @foreach($items as $m)
                <li>
                    <div>
                        <span class="badge">{{ \App\Models\Material::TYPES[$m->type] }}</span>
                        <strong>{{ $m->title }}</strong>
                        @unless($m->is_published)<span class="badge badge-off">Oculto</span>@endunless
                        @if($m->description)<div class="small muted">{{ $m->description }}</div>@endif
                        @if($m->type === 'archivo')<div class="small"><a href="{{ route('materials.download', $m) }}">{{ $m->file_name }}</a> · {{ $m->humanSize() }}</div>@endif
                        @if($m->type === 'enlace')<div class="small"><a href="{{ $m->url }}" target="_blank" rel="noopener">{{ \Illuminate\Support\Str::limit($m->url, 70) }}</a></div>@endif
                    </div>
                    <div class="actions">
                        <a class="btn btn-sm btn-sec" href="{{ route('teacher.materials.edit', $m) }}">Editar</a>
                        <form class="inline" method="POST" action="{{ route('teacher.materials.destroy', $m) }}" onsubmit="return confirm('¿Eliminar este material?')">@csrf @method('DELETE')<button class="btn btn-sm btn-danger">Eliminar</button></form>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
@empty
    <div class="card empty">Aún no hay material publicado.</div>
@endforelse
@endsection
