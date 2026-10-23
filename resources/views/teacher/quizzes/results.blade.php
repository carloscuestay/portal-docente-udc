@extends('layouts.app')
@section('title', 'Resultados · '.$quiz->title)
@section('content')
<div class="breadcrumb"><a href="{{ route('dashboard') }}">Inicio</a> / <a href="{{ route('teacher.subjects.show', $subject) }}">{{ $subject->code }}</a> / <a href="{{ route('teacher.quizzes.show', $quiz) }}">{{ $quiz->title }}</a> / Resultados</div>
<div class="page-head">
    <div><h1>Resultados</h1><p class="muted">{{ $quiz->title }} · nota sobre {{ $quiz->grade_scale }}</p></div>
    <a class="btn" href="{{ route('teacher.quizzes.export', $quiz) }}">Descargar notas (CSV/Excel)</a>
</div>

@php
    $presented = $grades->count();
    $avg = $presented ? round($grades->avg(), 2) : null;
    $passing = $grades->filter(fn ($g) => $g >= $quiz->grade_scale * 0.6)->count();
@endphp
<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(170px,1fr))">
    <div class="card"><div class="muted small">Presentaron</div><div class="big-grade">{{ $presented }}/{{ $students->count() }}</div></div>
    <div class="card"><div class="muted small">Promedio</div><div class="big-grade">{{ $avg ?? '—' }}</div></div>
    <div class="card"><div class="muted small">Aprobaron (≥ 60 %)</div><div class="big-grade">{{ $passing }}</div></div>
</div>

<h2>Por estudiante</h2>
<div class="table-wrap">
<table>
    <thead><tr><th>Estudiante</th><th>Intentos</th><th>Mejor nota</th><th>Estado</th><th></th></tr></thead>
    <tbody>
    @foreach($students as $s)
        @php $list = $attempts->get($s->id, collect())->sortBy('number'); @endphp
        <tr>
            <td>{{ $s->name }}<br><span class="small muted">{{ $s->student_code }} · {{ $s->email }}</span></td>
            <td>{{ $list->count() }}</td>
            <td><strong>{{ isset($grades[$s->id]) ? number_format($grades[$s->id], 2) : '—' }}</strong></td>
            <td>
                @forelse($list as $a)
                    <div class="small">#{{ $a->number }}:
                        @if($a->status === 'en_curso')<span class="badge badge-warn">En curso</span>
                        @elseif($a->status === 'enviado')<span class="badge badge-warn">Por calificar</span>
                        @else<span class="badge badge-ok">Calificado</span>@endif
                        {{ $a->grade !== null ? number_format($a->grade, 2) : '' }}
                        <a href="{{ route('teacher.attempts.show', $a) }}">ver</a>
                    </div>
                @empty
                    <span class="badge badge-off">No ha presentado</span>
                @endforelse
            </td>
            <td></td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>

<h2>Análisis por pregunta</h2>
<p class="small muted">Porcentaje promedio de acierto. Valores muy bajos pueden indicar una pregunta difícil, ambigua o con clave errada.</p>
<div class="table-wrap">
<table>
    <thead><tr><th>#</th><th>Pregunta</th><th>Tipo</th><th>% acierto</th></tr></thead>
    <tbody>
    @foreach($questions as $i => $q)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ \Illuminate\Support\Str::limit($q->stem, 110) }}</td>
            <td class="small">{{ $q->typeLabel() }}</td>
            <td>
                @if($itemStats[$q->id] === null) — @else
                    <span class="badge {{ $itemStats[$q->id] >= 60 ? 'badge-ok' : ($itemStats[$q->id] >= 30 ? 'badge-warn' : 'badge-err') }}">{{ $itemStats[$q->id] }} %</span>
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
@endsection
