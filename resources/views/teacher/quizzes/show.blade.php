@extends('layouts.app')
@section('title', $quiz->title)
@section('content')
@php $st = $quiz->statusLabel(); $total = $quiz->questions->sum('points'); @endphp
<div class="breadcrumb"><a href="{{ route('dashboard') }}">Inicio</a> / <a href="{{ route('teacher.subjects.show', $subject) }}">{{ $subject->code }}</a> / Evaluación</div>
<div class="page-head">
    <div>
        <h1>{{ $quiz->title }}</h1>
        <div class="muted small">
            <span class="badge {{ ['Abierta' => 'badge-ok', 'Programada' => 'badge-warn', 'Borrador' => 'badge-off', 'Cerrada' => 'badge-err'][$st] }}">{{ $st }}</span>
            {{ $quiz->questions->count() }} preguntas · {{ rtrim(rtrim(number_format($total, 2), '0'), '.') }} puntos ·
            {{ $quiz->time_limit_minutes ? $quiz->time_limit_minutes.' min' : 'Sin límite de tiempo' }} ·
            {{ $quiz->max_attempts }} intento(s) ·
            {{ $quiz->opens_at?->format('d/m/Y H:i') ?? 'sin apertura' }} → {{ $quiz->closes_at?->format('d/m/Y H:i') ?? 'sin cierre' }}
        </div>
    </div>
    <div class="actions">
        <a class="btn btn-sec" href="{{ route('teacher.quizzes.edit', $quiz) }}">Configurar / publicar</a>
        <a class="btn btn-sec" href="{{ route('teacher.quizzes.preview', $quiz) }}" target="_blank">Vista previa</a>
        <a class="btn" href="{{ route('teacher.quizzes.results', $quiz) }}">Resultados ({{ $quiz->attempts_count }})</a>
    </div>
</div>

@if(! $quiz->is_published && $quiz->questions->isNotEmpty())
    <div class="alert alert-warn">La evaluación está en borrador. Cuando termine, vaya a <a href="{{ route('teacher.quizzes.edit', $quiz) }}">Configurar / publicar</a>.</div>
@endif
@if($quiz->attempts_count > 0)
    <div class="alert alert-warn">Ya hay intentos registrados. Si modifica preguntas, los intentos enviados conservan su calificación.</div>
@endif

<div class="card">
    <h3>Agregar pregunta</h3>
    <div class="actions">
        @foreach(\App\Models\Question::TYPES as $k => $label)
            <a class="btn btn-sec btn-sm" href="{{ route('teacher.questions.create', [$quiz, 'tipo' => $k]) }}">+ {{ $label }}</a>
        @endforeach
    </div>
</div>

@forelse($quiz->questions as $i => $q)
    <div class="question">
        <div class="qhead">
            <span><strong>Pregunta {{ $i + 1 }}</strong> · {{ $q->typeLabel() }} · {{ rtrim(rtrim(number_format($q->points, 2), '0'), '.') }} pt</span>
            <span class="actions no-print">
                <form class="inline" method="POST" action="{{ route('teacher.questions.move', [$q, 'arriba']) }}">@csrf<button class="btn btn-sm btn-sec" title="Subir" @disabled($loop->first)>↑</button></form>
                <form class="inline" method="POST" action="{{ route('teacher.questions.move', [$q, 'abajo']) }}">@csrf<button class="btn btn-sm btn-sec" title="Bajar" @disabled($loop->last)>↓</button></form>
                <a class="btn btn-sm btn-sec" href="{{ route('teacher.questions.edit', $q) }}">Editar</a>
                <form class="inline" method="POST" action="{{ route('teacher.questions.destroy', $q) }}" onsubmit="return confirm('¿Eliminar la pregunta?')">@csrf @method('DELETE')<button class="btn btn-sm btn-danger">Eliminar</button></form>
            </span>
        </div>
        @include('partials.question-review', ['q' => $q, 'answer' => null, 'reveal' => true, 'showResponse' => false])
    </div>
@empty
    <div class="card empty">Aún no hay preguntas. Use los botones de arriba para agregarlas.</div>
@endforelse

<details class="card">
    <summary>Duplicar evaluación</summary>
    <p class="small muted">Crea una copia en borrador (con todas las preguntas) en esta u otra de sus asignaturas.</p>
    <form method="POST" action="{{ route('teacher.quizzes.duplicate', $quiz) }}" class="actions">
        @csrf
        <select name="subject_id" style="max-width:420px">
            @foreach(auth()->user()->taughtSubjects()->orderByDesc('period')->get() as $s)
                <option value="{{ $s->id }}" @selected($s->id === $quiz->subject_id)>{{ $s->label }}</option>
            @endforeach
        </select>
        <button class="btn btn-sec">Duplicar</button>
    </form>
</details>
@endsection
