@extends('layouts.app')
@section('title', $quiz->exists ? 'Editar evaluación' : 'Nueva evaluación')
@section('content')
<div class="breadcrumb"><a href="{{ route('dashboard') }}">Inicio</a> / <a href="{{ route('teacher.subjects.show', $subject) }}">{{ $subject->code }}</a> / Evaluación</div>
<h1>{{ $quiz->exists ? 'Configurar evaluación' : 'Nueva evaluación' }}</h1>
<form class="card" method="POST" action="{{ $quiz->exists ? route('teacher.quizzes.update', $quiz) : route('teacher.quizzes.store', $subject) }}">
    @csrf
    @if($quiz->exists) @method('PUT') @endif
    <label for="title">Título</label>
    <input id="title" type="text" name="title" value="{{ old('title', $quiz->title) }}" required maxlength="255" placeholder="Parcial 1 · Simulacro Saber Pro – Razonamiento cuantitativo">
    <label for="instructions">Instrucciones para el estudiante</label>
    <textarea id="instructions" name="instructions">{{ old('instructions', $quiz->instructions) }}</textarea>
    <div class="row">
        <div><label for="opens_at">Apertura <span class="hint">(hora Colombia)</span></label>
            <input id="opens_at" type="datetime-local" name="opens_at" value="{{ old('opens_at', $quiz->opens_at?->format('Y-m-d\TH:i')) }}"></div>
        <div><label for="closes_at">Cierre</label>
            <input id="closes_at" type="datetime-local" name="closes_at" value="{{ old('closes_at', $quiz->closes_at?->format('Y-m-d\TH:i')) }}"></div>
    </div>
    <div class="row">
        <div><label for="time_limit_minutes">Tiempo límite (minutos) <span class="hint">vacío = sin límite</span></label>
            <input id="time_limit_minutes" type="number" min="1" max="600" name="time_limit_minutes" value="{{ old('time_limit_minutes', $quiz->time_limit_minutes) }}"></div>
        <div><label for="max_attempts">Intentos permitidos</label>
            <input id="max_attempts" type="number" min="1" max="10" name="max_attempts" value="{{ old('max_attempts', $quiz->max_attempts) }}" required></div>
        <div><label for="grade_scale">Nota máxima</label>
            <input id="grade_scale" type="number" step="0.1" min="1" max="100" name="grade_scale" value="{{ old('grade_scale', $quiz->grade_scale) }}" required></div>
    </div>
    <label class="check"><input type="checkbox" name="shuffle_questions" value="1" @checked(old('shuffle_questions', $quiz->shuffle_questions))> Barajar el orden de las preguntas para cada estudiante</label>
    <label class="check"><input type="checkbox" name="shuffle_options" value="1" @checked(old('shuffle_options', $quiz->shuffle_options))> Barajar opciones de respuesta y columnas de apareamiento</label>
    <label class="check"><input type="checkbox" name="show_results" value="1" @checked(old('show_results', $quiz->show_results))> Mostrar respuestas correctas y retroalimentación después del cierre</label>
    @if($quiz->exists)
        <label class="check"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $quiz->is_published))> <strong>Publicada</strong> (visible para los estudiantes en la ventana de fechas)</label>
    @else
        <p class="small muted">La evaluación se crea como borrador; publíquela cuando tenga las preguntas listas.</p>
    @endif
    <div class="actions" style="margin-top:16px">
        <button class="btn" type="submit">Guardar</button>
        <a class="btn btn-sec" href="{{ $quiz->exists ? route('teacher.quizzes.show', $quiz) : route('teacher.subjects.show', $subject) }}">Cancelar</a>
    </div>
</form>
@if($quiz->exists)
<details class="card">
    <summary>Eliminar evaluación</summary>
    <p class="muted">Se eliminarán las preguntas y todos los intentos y notas de los estudiantes.</p>
    <form method="POST" action="{{ route('teacher.quizzes.destroy', $quiz) }}" onsubmit="return confirm('¿Eliminar la evaluación y sus resultados?')">@csrf @method('DELETE')
        <button class="btn btn-danger">Eliminar evaluación</button></form>
</details>
@endif
@endsection
