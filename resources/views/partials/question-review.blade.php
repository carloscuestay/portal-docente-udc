{{--
  Muestra una pregunta con (opcionalmente) la respuesta del estudiante y la clave.
  Parámetros: $q (Question), $answer (Answer|null), $reveal (bool: mostrar clave), $showResponse (bool)
--}}
@php
    $d = $q->data;
    $resp = $answer?->response ?? [];
    $letters = range('A', 'Z');
    $norm = fn ($s) => \App\Services\QuizGrader::normalize((string) $s);
@endphp
@if($q->context)<div class="context">{{ $q->context }}</div>@endif
<div class="stem">{{ $q->stem }}</div>

@switch($q->type)
    @case(\App\Models\Question::SELECCION)
        @foreach($d['options'] as $k => $o)
            @php
                $chosen = ($resp['choice'] ?? null) === $o['id'];
                $isCorrect = $o['id'] === $d['correct'];
                $cls = $reveal && $isCorrect ? 'correct' : ($reveal && $showResponse && $chosen && ! $isCorrect ? 'wrong' : '');
            @endphp
            <div class="option {{ $cls }}">
                <span class="letter">{{ $letters[$k] }}.</span>
                <span>{{ $o['text'] }}
                    @if($showResponse && $chosen) <em class="small">← respuesta del estudiante</em>@endif
                    @if($reveal && $isCorrect) <strong class="small result-ok">✓ clave</strong>@endif
                </span>
            </div>
        @endforeach
        @if($showResponse && empty($resp['choice']))<p class="small muted">Sin responder.</p>@endif
        @break

    @case(\App\Models\Question::APAREAMIENTO)
        @php $rights = collect($d['pairs'])->pluck('right', 'id')->merge(collect($d['distractors'] ?? [])->pluck('text', 'id')); @endphp
        <div class="table-wrap"><table>
            <thead><tr><th>Premisa</th>@if($showResponse)<th>Respuesta del estudiante</th>@endif @if($reveal)<th>Correcta</th>@endif</tr></thead>
            <tbody>
            @foreach($d['pairs'] as $p)
                @php $given = $resp['matches'][$p['id']] ?? null; $ok = $given === $p['id']; @endphp
                <tr>
                    <td>{{ $p['left'] }}</td>
                    @if($showResponse)<td class="{{ $reveal ? ($ok ? 'result-ok' : 'result-bad') : '' }}">{{ $given ? ($rights[$given] ?? '—') : 'Sin responder' }} @if($reveal){{ $ok ? '✓' : '✗' }}@endif</td>@endif
                    @if($reveal)<td>{{ $p['right'] }}</td>@endif
                </tr>
            @endforeach
            </tbody>
        </table></div>
        @if($reveal && ! empty($d['distractors']))<p class="small muted">Distractores: {{ collect($d['distractors'])->pluck('text')->join(' · ') }}</p>@endif
        @break

    @case(\App\Models\Question::COMPLETAR)
        <div class="fill-text">
            @foreach(\App\Services\QuestionBuilder::segments($d['text']) as $seg)
                @if(isset($seg['blank']))
                    @php
                        $i = $seg['blank']; $given = $resp['blanks'][$i] ?? '';
                        $ok = $given !== '' && in_array($norm($given), array_map($norm, $d['blanks'][$i]), true);
                    @endphp
                    @if($showResponse)
                        <span class="badge {{ $reveal ? ($ok ? 'badge-ok' : 'badge-err') : '' }}">{{ $given !== '' ? $given : '(vacío)' }}</span>
                    @endif
                    @if($reveal && (! $showResponse || ! $ok))
                        <span class="badge">{{ implode(' / ', $d['blanks'][$i]) }}</span>
                    @endif
                @else{{ $seg['text'] }}@endif
            @endforeach
        </div>
        @if($reveal && ! empty($d['word_bank']))<p class="small muted">Con banco de palabras{{ ! empty($d['distractors']) ? ' · distractores: '.implode(', ', $d['distractors']) : '' }}.</p>@endif
        @break

    @case(\App\Models\Question::LIBRE)
        <p class="small muted">Respuesta libre · máximo {{ $d['max_chars'] }} caracteres · calificación manual.</p>
        @if($showResponse)
            <div class="context" style="border-color:var(--suave)">{{ ($resp['text'] ?? '') !== '' ? $resp['text'] : 'Sin respuesta.' }}</div>
            <p class="small muted">{{ mb_strlen($resp['text'] ?? '') }} / {{ $d['max_chars'] }} caracteres</p>
        @endif
        @if($reveal && ! empty($d['reference']))<div class="small"><strong>Respuesta de referencia:</strong> <span class="prose">{{ $d['reference'] }}</span></div>@endif
        @break
@endswitch

@if($reveal && $q->feedback)
    <div class="alert alert-ok small" style="margin-top:12px"><strong>Retroalimentación:</strong> <span class="prose">{{ $q->feedback }}</span></div>
@endif
