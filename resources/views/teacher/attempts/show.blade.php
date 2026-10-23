@extends('layouts.app')
@section('title', 'Intento · '.$attempt->user->name)
@section('content')
<div class="breadcrumb"><a href="{{ route('dashboard') }}">Inicio</a> / <a href="{{ route('teacher.quizzes.results', $quiz) }}">{{ $quiz->title }}</a> / Intento</div>
<div class="page-head">
    <div>
        <h1>{{ $attempt->user->name }}</h1>
        <p class="muted">{{ $attempt->user->email }} · Intento #{{ $attempt->number }} ·
            Inició {{ $attempt->started_at->format('d/m/Y H:i') }}
            @if($attempt->submitted_at) · Envió {{ $attempt->submitted_at->format('d/m/Y H:i') }} ({{ $attempt->started_at->diffInMinutes($attempt->submitted_at) < 1 ? '<1' : (int) $attempt->started_at->diffInMinutes($attempt->submitted_at) }} min)@endif
        </p>
    </div>
    <div class="card" style="margin:0;text-align:center;min-width:170px">
        <div class="muted small">Nota</div>
        <div class="big-grade">{{ $attempt->grade !== null ? number_format($attempt->grade, 2) : '—' }}</div>
        <div class="small muted">{{ $attempt->score }} / {{ $attempt->max_score }} pts</div>
    </div>
</div>

@if($attempt->isInProgress())
    <div class="alert alert-warn">El estudiante aún está presentando esta evaluación.</div>
@endif

<form method="POST" action="{{ route('teacher.attempts.grade', $attempt) }}">
    @csrf @method('PUT')
    @foreach($questions as $i => $q)
        @php $a = $answers->get($q->id); @endphp
        <div class="question">
            <div class="qhead">
                <span><strong>Pregunta {{ $i + 1 }}</strong> · {{ $q->typeLabel() }}</span>
                <span>
                    @if($a && $a->score === null)<span class="badge badge-warn">Por calificar</span>@endif
                </span>
            </div>
            @include('partials.question-review', ['q' => $q, 'answer' => $a, 'reveal' => true, 'showResponse' => true])
            @unless($attempt->isInProgress())
            <div class="row" style="margin-top:12px">
                <div><label for="s{{ $q->id }}">Puntaje (máx. {{ $q->points }})</label>
                    <input id="s{{ $q->id }}" type="number" step="0.01" min="0" max="{{ $q->points }}" name="scores[{{ $q->id }}]" value="{{ $a?->score }}" @if($q->isManual() && $a && $a->score === null) required @endif></div>
                <div style="grid-column:span 2"><label for="f{{ $q->id }}">Comentario para el estudiante</label>
                    <input id="f{{ $q->id }}" type="text" name="feedback[{{ $q->id }}]" value="{{ $a?->teacher_feedback }}" maxlength="5000"></div>
            </div>
            @endunless
        </div>
    @endforeach
    @unless($attempt->isInProgress())
        <div class="actions"><button class="btn" type="submit">Guardar calificación</button></div>
    @endunless
</form>

<details class="card" style="margin-top:24px">
    <summary>Anular intento</summary>
    <p class="small muted">Elimina este intento y sus respuestas, permitiendo que el estudiante presente de nuevo (por ejemplo, por fallas de conexión).</p>
    <form method="POST" action="{{ route('teacher.attempts.destroy', $attempt) }}" onsubmit="return confirm('¿Anular el intento? Se perderán sus respuestas.')">@csrf @method('DELETE')
        <button class="btn btn-danger">Anular intento</button></form>
</details>
@endsection
