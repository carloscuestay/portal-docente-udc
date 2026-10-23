@extends('layouts.app')
@section('title', $quiz->title)
@section('content')
@php
    use App\Models\Question;
    use App\Support\Seeded;
    $seedBase = $attempt ? 'a'.$attempt->id : 'preview';
    $letters = range('A', 'Z');
    $action = $attempt ? route('student.attempts.submit', $attempt) : '#';
@endphp

<div class="quiz-bar">
    <div class="container">
        <div><strong>{{ $quiz->title }}</strong>
            @if($preview)<span class="badge badge-warn">Vista previa del docente</span>@endif
            <div class="save-state" id="save-state">{{ $attempt ? 'Las respuestas se guardan automáticamente.' : '' }}</div></div>
        <div class="actions">
            @if($attempt && $attempt->deadline_at)
                <span class="timer" id="timer" data-seconds="{{ $attempt->secondsLeft() }}" aria-live="polite">--:--</span>
            @endif
            @unless($preview)<button class="btn btn-ok" type="submit" form="quiz-form">Enviar evaluación</button>@endunless
        </div>
    </div>
</div>

@if($quiz->instructions)
    <details class="card"><summary>Instrucciones</summary><div class="prose" style="margin-top:8px">{{ $quiz->instructions }}</div></details>
@endif

<div class="card no-print">
    <div class="small muted" style="margin-bottom:8px">Navegación ({{ $questions->count() }} preguntas)</div>
    <div class="nav-q">@foreach($questions as $i => $q)<a href="#q{{ $q->id }}" data-nav="{{ $q->id }}">{{ $i + 1 }}</a>@endforeach</div>
</div>

<form id="quiz-form" method="POST" action="{{ $action }}" data-save-url="{{ $attempt ? route('student.attempts.save', $attempt) : '' }}" autocomplete="off">
    @csrf
    @foreach($questions as $i => $q)
        @php
            $d = $q->data;
            $r = $answers->get($q->id)?->response ?? [];
            $name = "answers[{$q->id}]";
            $shuffle = $quiz->shuffle_options;
        @endphp
        <section class="question" id="q{{ $q->id }}" data-question="{{ $q->id }}">
            <div class="qhead"><span><strong>Pregunta {{ $i + 1 }}</strong> de {{ $questions->count() }}</span><span>{{ rtrim(rtrim(number_format($q->points, 2), '0'), '.') }} pt</span></div>
            @if($q->context)<div class="context">{{ $q->context }}</div>@endif
            <div class="stem">{{ $q->stem }}</div>

            @switch($q->type)
                @case(Question::SELECCION)
                    @php $opts = $shuffle && empty($d['fixed_order']) ? Seeded::shuffle($d['options'], $seedBase.'q'.$q->id) : $d['options']; @endphp
                    @foreach($opts as $k => $o)
                        <label class="option">
                            <input type="radio" name="{{ $name }}[choice]" value="{{ $o['id'] }}" @checked(($r['choice'] ?? null) === $o['id'])>
                            <span class="letter">{{ $letters[$k] }}.</span><span>{{ $o['text'] }}</span>
                        </label>
                    @endforeach
                    @break

                @case(Question::APAREAMIENTO)
                    @php
                        $choices = collect($d['pairs'])->map(fn ($p) => ['id' => $p['id'], 'text' => $p['right']])->concat($d['distractors'] ?? [])->all();
                        $choices = $shuffle ? Seeded::shuffle($choices, $seedBase.'m'.$q->id) : $choices;
                        $lefts = $shuffle ? Seeded::shuffle($d['pairs'], $seedBase.'l'.$q->id) : $d['pairs'];
                    @endphp
                    <div class="context" style="border-color:var(--verde);white-space:normal"><strong>Columna B</strong>
                        @foreach($choices as $k => $c)<div>{{ $letters[$k] }}. {{ $c['text'] }}</div>@endforeach
                    </div>
                    @foreach($lefts as $n => $p)
                        <div class="match-row">
                            <div>{{ $n + 1 }}. {{ $p['left'] }}</div>
                            <select name="{{ $name }}[matches][{{ $p['id'] }}]" aria-label="Relación para {{ $p['left'] }}">
                                <option value="">— Seleccione —</option>
                                @foreach($choices as $k => $c)
                                    <option value="{{ $c['id'] }}" @selected(($r['matches'][$p['id']] ?? null) === $c['id'])>{{ $letters[$k] }}. {{ \Illuminate\Support\Str::limit($c['text'], 60) }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                    @break

                @case(Question::COMPLETAR)
                    @php
                        $bank = null;
                        if (! empty($d['word_bank'])) {
                            $bank = collect($d['blanks'])->map(fn ($b) => $b[0])->concat($d['distractors'] ?? [])->unique()->values()->all();
                            $bank = Seeded::shuffle($bank, $seedBase.'b'.$q->id);
                        }
                    @endphp
                    @if($bank)<p class="small muted">Banco de palabras: {{ implode(' · ', $bank) }}</p>@endif
                    <div class="fill-text">
                        @foreach(\App\Services\QuestionBuilder::segments($d['text']) as $seg)
                            @if(isset($seg['blank']))
                                @if($bank)
                                    <select name="{{ $name }}[blanks][{{ $seg['blank'] }}]" aria-label="Espacio {{ $seg['blank'] + 1 }}">
                                        <option value="">______</option>
                                        @foreach($bank as $w)<option value="{{ $w }}" @selected(($r['blanks'][$seg['blank']] ?? '') === $w)>{{ $w }}</option>@endforeach
                                    </select>
                                @else
                                    <input type="text" name="{{ $name }}[blanks][{{ $seg['blank'] }}]" value="{{ $r['blanks'][$seg['blank']] ?? '' }}" maxlength="200" aria-label="Espacio {{ $seg['blank'] + 1 }}" spellcheck="false">
                                @endif
                            @else{{ $seg['text'] }}@endif
                        @endforeach
                    </div>
                    @break

                @case(Question::LIBRE)
                    <textarea name="{{ $name }}[text]" maxlength="{{ $d['max_chars'] }}" data-counter rows="6" aria-label="Respuesta">{{ $r['text'] ?? '' }}</textarea>
                    <div class="counter"><span data-count>{{ mb_strlen($r['text'] ?? '') }}</span> / {{ $d['max_chars'] }} caracteres</div>
                    @break
            @endswitch
        </section>
    @endforeach

    @unless($preview)
        <div class="card">
            <p class="muted">Revise sus respuestas antes de enviar. Una vez enviada no podrá modificarla.</p>
            <button class="btn btn-ok" type="submit">Enviar evaluación</button>
        </div>
    @endunless
</form>
@endsection

@push('scripts')
<script>
(function () {
  var form = document.getElementById('quiz-form');
  var preview = {{ $preview ? 'true' : 'false' }};
  var state = document.getElementById('save-state');
  var dirty = false, submitting = false, timerId = null;

  // Contador de caracteres
  form.querySelectorAll('[data-counter]').forEach(function (ta) {
    var out = ta.parentNode.querySelector('[data-count]');
    var max = parseInt(ta.getAttribute('maxlength'), 10);
    var upd = function () { out.textContent = ta.value.length; out.parentNode.classList.toggle('over', ta.value.length >= max); };
    ta.addEventListener('input', upd); upd();
  });

  // Marcar preguntas respondidas en la navegación
  function markAnswered() {
    form.querySelectorAll('[data-question]').forEach(function (sec) {
      var done = Array.prototype.some.call(sec.querySelectorAll('input,select,textarea'), function (el) {
        if (el.type === 'radio') return el.checked;
        return el.value && el.value.trim() !== '';
      });
      var nav = document.querySelector('[data-nav="' + sec.dataset.question + '"]');
      if (nav) nav.classList.toggle('done', done);
    });
  }
  markAnswered();

  if (preview) { form.addEventListener('submit', function (e) { e.preventDefault(); }); return; }

  // Autoguardado
  function save(beacon) {
    if (!dirty || submitting) return;
    var data = new FormData(form);
    if (beacon && navigator.sendBeacon) { navigator.sendBeacon(form.dataset.saveUrl, data); dirty = false; return; }
    state.textContent = 'Guardando…';
    fetch(form.dataset.saveUrl, { method: 'POST', body: data, headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
      .then(function (r) { return r.json().then(function (j) { return { status: r.status, body: j }; }); })
      .then(function (res) {
        if (res.status === 409) { state.textContent = 'El tiempo terminó. Enviando…'; finish(); return; }
        if (res.body.ok) { dirty = false; state.textContent = 'Guardado a las ' + res.body.saved_at; }
        if (res.body.seconds_left !== null && res.body.seconds_left !== undefined) syncTimer(res.body.seconds_left);
      })
      .catch(function () { state.textContent = '⚠ Sin conexión: sus respuestas se guardarán cuando vuelva la red.'; });
  }
  var debounce;
  form.addEventListener('change', function () { dirty = true; markAnswered(); clearTimeout(debounce); debounce = setTimeout(save, 1500); });
  form.addEventListener('input', function () { dirty = true; clearTimeout(debounce); debounce = setTimeout(function () { markAnswered(); save(); }, 4000); });
  setInterval(save, 30000);
  document.addEventListener('visibilitychange', function () { if (document.visibilityState === 'hidden') save(true); });

  function finish() { submitting = true; form.submit(); }
  form.addEventListener('submit', function (e) {
    if (submitting) return;
    var pending = document.querySelectorAll('.nav-q a:not(.done)').length;
    var msg = pending ? 'Tiene ' + pending + ' pregunta(s) sin responder. ¿Enviar de todas formas?' : '¿Enviar la evaluación? No podrá modificarla después.';
    if (!confirm(msg)) { e.preventDefault(); return; }
    submitting = true;
  });
  window.addEventListener('beforeunload', function (e) { if (dirty && !submitting) { save(true); e.preventDefault(); e.returnValue = ''; } });

  // Temporizador
  var timer = document.getElementById('timer');
  var endAt = null;
  function syncTimer(seconds) { endAt = Date.now() + seconds * 1000; }
  if (timer) {
    syncTimer(parseInt(timer.dataset.seconds, 10));
    var tick = function () {
      var left = Math.max(0, Math.round((endAt - Date.now()) / 1000));
      var h = Math.floor(left / 3600), m = Math.floor((left % 3600) / 60), s = left % 60;
      timer.textContent = (h ? h + ':' : '') + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
      timer.classList.toggle('low', left <= 300);
      if (left <= 0) { clearInterval(timerId); state.textContent = 'Tiempo agotado. Enviando…'; finish(); }
    };
    tick(); timerId = setInterval(tick, 1000);
  }
})();
</script>
@endpush
