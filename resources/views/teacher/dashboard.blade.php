@extends('layouts.app')
@section('title', 'Panel docente')
@section('content')
<div class="page-head">
    <div>
        <h1>Panel docente</h1>
        <p class="muted">Bienvenido, {{ auth()->user()->name }}.</p>
    </div>
    <a class="btn" href="{{ route('teacher.subjects.create') }}">+ Nueva asignatura</a>
</div>

@if($requests->isNotEmpty())
<div class="card">
    <h3>Solicitudes de acceso pendientes ({{ $requests->count() }})</h3>
    <ul class="list">
        @foreach($requests as $r)
            <li>
                <div><strong>{{ $r->name }}</strong> · {{ $r->email }} · Código {{ $r->student_code }}<br>
                    <span class="small muted">{{ $r->subject->label }} · {{ $r->created_at->diffForHumans() }}</span></div>
                <div class="actions">
                    <form class="inline" method="POST" action="{{ route('teacher.requests.approve', $r) }}">@csrf<button class="btn btn-sm btn-ok">Aprobar</button></form>
                    <form class="inline" method="POST" action="{{ route('teacher.requests.reject', $r) }}">@csrf<button class="btn btn-sm btn-danger">Rechazar</button></form>
                </div>
            </li>
        @endforeach
    </ul>
</div>
@endif

@if($pendingGrading->isNotEmpty())
<div class="card">
    <h3>Respuestas abiertas por calificar</h3>
    <ul class="list">
        @foreach($pendingGrading as $a)
            <li><span>{{ $a->user->name }} · <span class="muted">{{ $a->quiz->title }} ({{ $a->quiz->subject->code }})</span></span>
                <a class="btn btn-sm" href="{{ route('teacher.attempts.show', $a) }}">Calificar</a></li>
        @endforeach
    </ul>
</div>
@endif

<h2>Mis asignaturas</h2>
@forelse($subjects->groupBy('program') as $program => $list)
    <h3 class="muted" style="margin-top:20px">{{ $program }}</h3>
    <div class="grid">
        @foreach($list as $s)
            <a class="card" href="{{ route('teacher.subjects.show', $s) }}" style="text-decoration:none;color:inherit">
                <div class="actions" style="justify-content:space-between">
                    <span class="badge">{{ $s->code }}{{ $s->group ? ' · G'.$s->group : '' }}</span>
                    <span class="badge {{ $s->is_active ? 'badge-ok' : 'badge-off' }}">{{ $s->is_active ? $s->period : 'Inactiva' }}</span>
                </div>
                <h3 style="margin-top:10px">{{ $s->name }}</h3>
                <div class="stats">
                    <span>{{ $s->enrollments_count }} estudiantes</span>
                    <span>{{ $s->materials_count }} materiales</span>
                    <span>{{ $s->quizzes_count }} evaluaciones</span>
                </div>
            </a>
        @endforeach
    </div>
@empty
    <div class="card empty">Aún no ha creado asignaturas. <a href="{{ route('teacher.subjects.create') }}">Cree la primera</a>.</div>
@endforelse
@endsection
