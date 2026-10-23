@extends('layouts.app')
@section('title', ($question->exists ? 'Editar' : 'Nueva').' pregunta')
@section('content')
@php
    use App\Models\Question;
    $d = $question->data ?? [];
    $type = old('type', $question->type);
    $letters = range('A', 'Z');
@endphp
<div class="breadcrumb"><a href="{{ route('dashboard') }}">Inicio</a> / <a href="{{ route('teacher.subjects.show', $quiz->subject_id) }}">{{ $quiz->subject->code }}</a> / <a href="{{ route('teacher.quizzes.show', $quiz) }}">{{ $quiz->title }}</a> / Pregunta</div>
<h1>{{ $question->exists ? 'Editar pregunta' : 'Nueva pregunta' }}</h1>
<p class="muted">{{ Question::TYPES[$type] }}</p>

<form class="card" method="POST" action="{{ $question->exists ? route('teacher.questions.update', $question) : route('teacher.questions.store', $quiz) }}">
    @csrf
    @if($question->exists) @method('PUT') @endif
    <input type="hidden" name="type" value="{{ $type }}">

    <label for="context">Contexto / caso <span class="hint">(opcional; texto, situación o datos sobre los que se basa la pregunta, como en Saber Pro)</span></label>
    <textarea id="context" name="context">{{ old('context', $question->context) }}</textarea>

    <label for="stem">Enunciado</label>
    <textarea id="stem" name="stem" required style="min-height:80px">{{ old('stem', $question->stem) }}</textarea>

    @switch($type)
        @case(Question::SELECCION)
            @php
                $opts = old('options', collect($d['options'] ?? [])->pluck('text')->all());
                $opts = array_pad($opts, 4, '');
                $correctIdx = old('correct', collect($d['options'] ?? [])->search(fn ($o) => $o['id'] === ($d['correct'] ?? null)));
            @endphp
            <label>Opciones <span class="hint">(marque la correcta; las vacías se ignoran)</span></label>
            <div id="options">
                @foreach($opts as $i => $o)
                    <div class="repeat-item">
                        <input type="radio" name="correct" value="{{ $i }}" @checked((string) $correctIdx === (string) $i) title="Correcta" required>
                        <strong data-letter>{{ $letters[$i] }}.</strong>
                        <input type="text" name="options[]" value="{{ $o }}" maxlength="2000">
                    </div>
                @endforeach
            </div>
            <button type="button" class="btn btn-sm btn-sec" data-add-option>+ Opción</button>
            @break

        @case(Question::APAREAMIENTO)
            @php
                $left = old('left', collect($d['pairs'] ?? [])->pluck('left')->all());
                $right = old('right', collect($d['pairs'] ?? [])->pluck('right')->all());
                $n = max(count($left), 4);
            @endphp
            <label>Pares a relacionar <span class="hint">(columna A ↔ columna B; al estudiante se le muestran barajadas)</span></label>
            <div id="pairs">
                @for($i = 0; $i < $n; $i++)
                    <div class="repeat-item">
                        <input type="text" name="left[]" value="{{ $left[$i] ?? '' }}" placeholder="Premisa {{ $i + 1 }}" maxlength="1000">
                        <span>↔</span>
                        <input type="text" name="right[]" value="{{ $right[$i] ?? '' }}" placeholder="Respuesta {{ $i + 1 }}" maxlength="1000">
                    </div>
                @endfor
            </div>
            <button type="button" class="btn btn-sm btn-sec" data-add-pair>+ Par</button>
            <label for="distractors">Distractores <span class="hint">(opcional; respuestas extra que no corresponden a ninguna premisa, una por línea)</span></label>
            <textarea id="distractors" name="distractors" style="min-height:70px">{{ old('distractors', collect($d['distractors'] ?? [])->pluck('text')->join("\n")) }}</textarea>
            <p class="small muted">Calificación proporcional: cada par correcto suma la parte correspondiente del puntaje.</p>
            @break

        @case(Question::COMPLETAR)
            <label for="text">Texto con espacios <span class="hint">Encierre cada respuesta en doble corchete. Use | para alternativas válidas.</span></label>
            <textarea id="text" name="text" required style="min-height:140px" placeholder="El patrón [[MVC|Modelo Vista Controlador]] separa la lógica en tres capas; la capa de [[vista]] presenta la información.">{{ old('text', $d['text'] ?? '') }}</textarea>
            <p class="small muted">La comparación ignora mayúsculas, tildes y espacios extra. Calificación proporcional por espacio.</p>
            <label class="check"><input type="checkbox" name="word_bank" value="1" @checked(old('word_bank', $d['word_bank'] ?? false))> Mostrar banco de palabras (el estudiante elige de una lista en lugar de escribir)</label>
            <label for="bank_distractors">Palabras distractoras para el banco <span class="hint">(una por línea)</span></label>
            <textarea id="bank_distractors" name="bank_distractors" style="min-height:70px">{{ old('bank_distractors', implode("\n", $d['distractors'] ?? [])) }}</textarea>
            @break

        @case(Question::LIBRE)
            <div class="row">
                <div><label for="max_chars">Límite de caracteres</label>
                    <input id="max_chars" type="number" name="max_chars" min="20" max="10000" value="{{ old('max_chars', $d['max_chars'] ?? 500) }}" required></div>
            </div>
            <label for="reference">Respuesta de referencia / rúbrica <span class="hint">(solo la ve el docente; y el estudiante si se muestran resultados)</span></label>
            <textarea id="reference" name="reference">{{ old('reference', $d['reference'] ?? '') }}</textarea>
            @break
    @endswitch

    <div class="row">
        <div><label for="points">Puntaje</label>
            <input id="points" type="number" step="0.1" min="0.1" max="100" name="points" value="{{ old('points', $question->points) }}" required></div>
    </div>
    <label for="feedback">Retroalimentación / justificación de la clave <span class="hint">(opcional)</span></label>
    <textarea id="feedback" name="feedback" style="min-height:70px">{{ old('feedback', $question->feedback) }}</textarea>

    <div class="actions" style="margin-top:16px">
        <button class="btn" type="submit" name="after" value="volver">Guardar</button>
        @unless($question->exists)
            <button class="btn btn-sec" type="submit" name="after" value="otra">Guardar y agregar otra</button>
        @endunless
        <a class="btn btn-sec" href="{{ route('teacher.quizzes.show', $quiz) }}">Cancelar</a>
    </div>
</form>

@push('scripts')
<script>
(function () {
  var L = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
  var addOpt = document.querySelector('[data-add-option]');
  if (addOpt) addOpt.addEventListener('click', function () {
    var box = document.getElementById('options'), n = box.children.length;
    if (n >= 8) return;
    var row = box.children[0].cloneNode(true);
    row.querySelector('input[type=radio]').value = n; row.querySelector('input[type=radio]').checked = false;
    row.querySelector('input[type=text]').value = '';
    row.querySelector('[data-letter]').textContent = L[n] + '.';
    box.appendChild(row);
  });
  var addPair = document.querySelector('[data-add-pair]');
  if (addPair) addPair.addEventListener('click', function () {
    var box = document.getElementById('pairs'), n = box.children.length;
    var row = box.children[0].cloneNode(true);
    row.querySelectorAll('input').forEach(function (i, k) { i.value = ''; i.placeholder = (k ? 'Respuesta ' : 'Premisa ') + (n + 1); });
    box.appendChild(row);
  });
})();
</script>
@endpush
@endsection
