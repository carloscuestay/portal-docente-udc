@extends('layouts.app')
@section('title', $quiz->title)
@section('content')
<div class="breadcrumb"><a href="{{ route('dashboard') }}">Inicio</a> / <a href="{{ route('student.subjects.show', $quiz->subject) }}">{{ $quiz->subject->code }}</a> / Evaluación</div>
<h1>{{ $quiz->title }}</h1>
<p class="muted">{{ $quiz->subject->name }}</p>

<div class="card">
    <div class="row">
        <div><div class="muted small">Preguntas</div><strong>{{ $quiz->questions_count }}</strong></div>
        <div><div class="muted small">Tiempo</div><strong>{{ $quiz->time_limit_minutes ? $quiz->time_limit_minutes.' minutos' : 'Sin límite' }}</strong></div>
        <div><div class="muted small">Intentos</div><strong>{{ $attempts->count() }} de {{ $quiz->max_attempts }}</strong></div>
        <div><div class="muted small">Cierra</div><strong>{{ $quiz->closes_at?->format('d/m/Y h:i a') ?? 'Sin fecha' }}</strong></div>
    </div>
    @if($quiz->instructions)<hr style="border:0;border-top:1px solid var(--borde);margin:16px 0"><div class="prose">{{ $quiz->instructions }}</div>@endif
</div>

@if($inProgress)
    <div class="alert alert-warn">Tiene un intento en curso.
        @if($inProgress->deadline_at) El tiempo sigue corriendo: termina a las {{ $inProgress->deadline_at->format('h:i a') }}.@endif</div>
    <a class="btn" href="{{ route('student.attempts.take', $inProgress) }}">Continuar evaluación</a>
@elseif($canStart)
    <div class="alert alert-warn small">
        Al iniciar comenzará a correr el tiempo{{ $quiz->time_limit_minutes ? ' ('.$quiz->time_limit_minutes.' minutos)' : '' }}.
        Sus respuestas se guardan automáticamente; si se cierra el navegador puede volver a entrar y continuar mientras haya tiempo.
        Al agotarse el tiempo la evaluación se envía con lo guardado.
    </div>
    <form method="POST" action="{{ route('student.quizzes.start', $quiz) }}" onsubmit="return confirm('¿Iniciar la evaluación ahora?')">
        @csrf <button class="btn" type="submit">{{ $attempts->isEmpty() ? 'Iniciar evaluación' : 'Iniciar nuevo intento' }}</button>
    </form>
@elseif(! $quiz->isOpen())
    <div class="alert alert-warn">
        @if($quiz->opens_at && now()->lt($quiz->opens_at)) La evaluación se habilita el {{ $quiz->opens_at->format('d/m/Y \a \l\a\s h:i a') }}.
        @else La evaluación está cerrada. @endif
    </div>
@else
    <div class="alert alert-warn">Ya utilizó todos los intentos permitidos.</div>
@endif

@if($attempts->where('status', '!=', 'en_curso')->isNotEmpty())
    <h2>Mis intentos</h2>
    <div class="table-wrap"><table>
        <thead><tr><th>#</th><th>Enviado</th><th>Nota</th><th></th></tr></thead>
        <tbody>
        @foreach($attempts->where('status', '!=', 'en_curso') as $a)
            <tr><td>{{ $a->number }}</td><td>{{ $a->submitted_at?->format('d/m/Y h:i a') }}</td>
                <td>{{ $a->status === 'calificado' ? number_format($a->grade, 2).' / '.$quiz->grade_scale : 'Pendiente de revisión' }}</td>
                <td><a href="{{ route('student.attempts.result', $a) }}">Ver detalle</a></td></tr>
        @endforeach
        </tbody>
    </table></div>
@endif
@endsection
