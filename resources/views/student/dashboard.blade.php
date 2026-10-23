@extends('layouts.app')
@section('title', 'Mis asignaturas')
@section('content')
<h1>Hola, {{ \Illuminate\Support\Str::words(auth()->user()->name, 1, '') }}</h1>
<p class="muted">Estas son las asignaturas en las que está habilitado.</p>

@if($openQuizzes->isNotEmpty())
<div class="card">
    <h3>Evaluaciones disponibles ahora</h3>
    <ul class="list">
        @foreach($openQuizzes as $q)
            <li><div><strong>{{ $q->title }}</strong><br><span class="small muted">{{ $q->subject->name }}
                @if($q->closes_at) · cierra {{ $q->closes_at->format('d/m/Y H:i') }} ({{ $q->closes_at->diffForHumans() }})@endif</span></div>
                <a class="btn btn-sm" href="{{ route('student.quizzes.show', $q) }}">Ir a la evaluación</a></li>
        @endforeach
    </ul>
</div>
@endif

<div class="grid">
    @forelse($subjects as $s)
        <a class="card" href="{{ route('student.subjects.show', $s) }}" style="text-decoration:none;color:inherit">
            <span class="badge">{{ $s->code }}{{ $s->group ? ' · G'.$s->group : '' }} · {{ $s->period }}</span>
            <h3 style="margin-top:10px">{{ $s->name }}</h3>
            <div class="small muted">{{ $s->program }} · Prof. {{ $s->teacher->name }}</div>
        </a>
    @empty
        <div class="card empty">No tiene asignaturas activas.</div>
    @endforelse
</div>
@endsection
