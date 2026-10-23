@extends('layouts.app')
@section('title', $subject->name)
@section('content')
<div class="breadcrumb"><a href="{{ route('dashboard') }}">Inicio</a> / {{ $subject->code }}</div>
<h1>{{ $subject->name }}</h1>
<p class="muted">{{ $subject->program }} · {{ $subject->code }}{{ $subject->group ? ' · Grupo '.$subject->group : '' }} · {{ $subject->period }} · Prof. {{ $subject->teacher->name }}</p>
@if($subject->description)<div class="card prose">{{ $subject->description }}</div>@endif

<h2>Evaluaciones</h2>
<div class="table-wrap">
<table>
    <thead><tr><th>Evaluación</th><th>Disponible</th><th>Mi nota</th><th></th></tr></thead>
    <tbody>
    @forelse($quizzes as $q)
        @php $mine = $myAttempts->get($q->id, collect()); $best = $mine->whereNotNull('grade')->max('grade'); @endphp
        <tr>
            <td><strong>{{ $q->title }}</strong><br><span class="small muted">{{ $q->questions_count }} preguntas{{ $q->time_limit_minutes ? ' · '.$q->time_limit_minutes.' min' : '' }}</span></td>
            <td class="small">
                {{ $q->opens_at?->format('d/m/Y H:i') ?? 'Desde ya' }} – {{ $q->closes_at?->format('d/m/Y H:i') ?? 'sin cierre' }}<br>
                <span class="badge {{ $q->isOpen() ? 'badge-ok' : 'badge-off' }}">{{ $q->statusLabel() }}</span>
            </td>
            <td>{{ $best !== null ? number_format($best, 2).' / '.$q->grade_scale : ($mine->isNotEmpty() ? 'En revisión' : '—') }}</td>
            <td><a class="btn btn-sm {{ $q->isOpen() ? '' : 'btn-sec' }}" href="{{ route('student.quizzes.show', $q) }}">{{ $q->isOpen() ? 'Abrir' : 'Ver' }}</a></td>
        </tr>
    @empty
        <tr><td colspan="4" class="empty">No hay evaluaciones publicadas.</td></tr>
    @endforelse
    </tbody>
</table>
</div>

<h2>Material de clase</h2>
@forelse($materials as $unit => $items)
    <div class="card">
        <h3>{{ $unit }}</h3>
        <ul class="list">
            @foreach($items as $m)
                <li>
                    <div style="flex:1;min-width:240px">
                        <strong>{{ $m->title }}</strong>
                        @if($m->description)<div class="small muted">{{ $m->description }}</div>@endif
                        @if($m->type === 'texto')
                            <details style="margin-top:6px"><summary>Leer</summary><div class="prose" style="margin-top:8px">{{ $m->body }}</div></details>
                        @endif
                    </div>
                    <div class="actions">
                        @if($m->type === 'archivo')
                            @if(str_ends_with(strtolower($m->file_name), '.pdf'))
                                <a class="btn btn-sm btn-sec" href="{{ route('materials.download', [$m, 'ver' => 1]) }}" target="_blank">Ver</a>
                            @endif
                            <a class="btn btn-sm" href="{{ route('materials.download', $m) }}">Descargar <span class="small">({{ $m->humanSize() }})</span></a>
                        @elseif($m->type === 'enlace')
                            <a class="btn btn-sm" href="{{ $m->url }}" target="_blank" rel="noopener">Abrir enlace ↗</a>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
@empty
    <div class="card empty">Aún no hay material publicado.</div>
@endforelse
@endsection
