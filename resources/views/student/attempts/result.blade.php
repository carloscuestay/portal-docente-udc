@extends('layouts.app')
@section('title', 'Resultado · '.$quiz->title)
@section('content')
<div class="breadcrumb"><a href="{{ route('dashboard') }}">Inicio</a> / <a href="{{ route('student.quizzes.show', $quiz) }}">{{ $quiz->title }}</a> / Resultado</div>
<div class="page-head">
    <div>
        <h1>{{ $quiz->title }}</h1>
        <p class="muted">Intento #{{ $attempt->number }} · enviado {{ $attempt->submitted_at?->format('d/m/Y h:i a') }}</p>
    </div>
    <div class="card" style="margin:0;text-align:center;min-width:180px">
        @if($attempt->status === 'calificado')
            <div class="muted small">Nota</div>
            <div class="big-grade">{{ number_format($attempt->grade, 2) }}</div>
            <div class="small muted">de {{ $quiz->grade_scale }} · {{ $attempt->score }} / {{ $attempt->max_score }} pts</div>
        @else
            <div class="muted small">Estado</div>
            <div style="font-weight:700;color:var(--ambar)">Pendiente de revisión</div>
            <div class="small muted">Puntaje parcial: {{ $attempt->score }} / {{ $attempt->max_score }}</div>
        @endif
    </div>
</div>

@if(! $reveal)
    <div class="alert alert-ok">Su evaluación fue recibida. {{ $quiz->show_results ? 'Las respuestas correctas se mostrarán cuando cierre la evaluación.' : '' }}</div>
@endif

@foreach($questions as $i => $q)
    @php $a = $answers->get($q->id); @endphp
    <div class="question">
        <div class="qhead">
            <span><strong>Pregunta {{ $i + 1 }}</strong></span>
            <span>
                @if(! $reveal)
                @elseif($a?->score === null) <span class="badge badge-warn">Por calificar</span>
                @else <strong>{{ rtrim(rtrim(number_format($a->score, 2), '0'), '.') ?: '0' }}</strong> / {{ rtrim(rtrim(number_format($q->points, 2), '0'), '.') }} pt @endif
            </span>
        </div>
        @include('partials.question-review', ['q' => $q, 'answer' => $a, 'reveal' => $reveal, 'showResponse' => true])
        @if($a?->teacher_feedback)
            <div class="alert alert-ok small" style="margin-top:10px"><strong>Comentario del docente:</strong> {{ $a->teacher_feedback }}</div>
        @endif
    </div>
@endforeach
@endsection
