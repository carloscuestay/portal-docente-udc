@extends('layouts.app')
@section('title', $subject->exists ? 'Editar asignatura' : 'Nueva asignatura')
@section('content')
<div class="breadcrumb"><a href="{{ route('dashboard') }}">Inicio</a> / {{ $subject->exists ? $subject->name : 'Nueva asignatura' }}</div>
<h1>{{ $subject->exists ? 'Editar asignatura' : 'Nueva asignatura' }}</h1>
<form class="card" method="POST" action="{{ $subject->exists ? route('teacher.subjects.update', $subject) : route('teacher.subjects.store') }}">
    @csrf
    @if($subject->exists) @method('PUT') @endif
    <div class="row">
        <div><label for="code">Código</label><input id="code" type="text" name="code" value="{{ old('code', $subject->code) }}" required maxlength="30"></div>
        <div><label for="period">Periodo <span class="hint">(AAAA-1 / AAAA-2)</span></label><input id="period" type="text" name="period" value="{{ old('period', $subject->period) }}" required pattern="\d{4}-[12]"></div>
        <div><label for="group">Grupo</label><input id="group" type="text" name="group" value="{{ old('group', $subject->group) }}" maxlength="20"></div>
    </div>
    <label for="name">Nombre de la asignatura</label>
    <input id="name" type="text" name="name" value="{{ old('name', $subject->name) }}" required maxlength="255">
    <label for="program">Programa</label>
    <input id="program" type="text" name="program" list="programs" value="{{ old('program', $subject->program) }}" required maxlength="120">
    <datalist id="programs">@foreach(\App\Models\Subject::PROGRAMS as $p)<option value="{{ $p }}">@endforeach</datalist>
    <label for="description">Descripción / presentación del curso</label>
    <textarea id="description" name="description">{{ old('description', $subject->description) }}</textarea>
    <label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $subject->is_active))> Asignatura activa (visible para los estudiantes)</label>
    <div class="actions" style="margin-top:16px">
        <button class="btn" type="submit">Guardar</button>
        <a class="btn btn-sec" href="{{ $subject->exists ? route('teacher.subjects.show', $subject) : route('dashboard') }}">Cancelar</a>
    </div>
</form>

@if($subject->exists)
<details class="card">
    <summary>Eliminar asignatura</summary>
    <p class="muted">Se eliminarán la lista de estudiantes, el material y todas las evaluaciones con sus resultados. Esta acción no se puede deshacer.</p>
    <form method="POST" action="{{ route('teacher.subjects.destroy', $subject) }}">
        @csrf @method('DELETE')
        <label for="confirm">Escriba el código <strong>{{ $subject->code }}</strong> para confirmar</label>
        <input id="confirm" type="text" name="confirm" required>
        <p><button class="btn btn-danger" type="submit">Eliminar definitivamente</button></p>
    </form>
</details>
@endif
@endsection
