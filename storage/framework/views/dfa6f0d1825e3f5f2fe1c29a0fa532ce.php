<?php $__env->startSection('title', $quiz->title); ?>
<?php $__env->startSection('content'); ?>
<?php
    use App\Models\Question;
    use App\Support\Seeded;
    $seedBase = $attempt ? 'a'.$attempt->id : 'preview';
    $letters = range('A', 'Z');
    $action = $attempt ? route('student.attempts.submit', $attempt) : '#';
?>

<div class="quiz-bar">
    <div class="container">
        <div><strong><?php echo e($quiz->title); ?></strong>
            <?php if($preview): ?><span class="badge badge-warn">Vista previa del docente</span><?php endif; ?>
            <div class="save-state" id="save-state"><?php echo e($attempt ? 'Las respuestas se guardan automáticamente.' : ''); ?></div></div>
        <div class="actions">
            <?php if($attempt && $attempt->deadline_at): ?>
                <span class="timer" id="timer" data-seconds="<?php echo e($attempt->secondsLeft()); ?>" aria-live="polite">--:--</span>
            <?php endif; ?>
            <?php if (! ($preview)): ?><button class="btn btn-ok" type="submit" form="quiz-form">Enviar evaluación</button><?php endif; ?>
        </div>
    </div>
</div>

<?php if($quiz->instructions): ?>
    <details class="card"><summary>Instrucciones</summary><div class="prose" style="margin-top:8px"><?php echo e($quiz->instructions); ?></div></details>
<?php endif; ?>

<div class="card no-print">
    <div class="small muted" style="margin-bottom:8px">Navegación (<?php echo e($questions->count()); ?> preguntas)</div>
    <div class="nav-q"><?php $__currentLoopData = $questions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $q): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><a href="#q<?php echo e($q->id); ?>" data-nav="<?php echo e($q->id); ?>"><?php echo e($i + 1); ?></a><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></div>
</div>

<form id="quiz-form" method="POST" action="<?php echo e($action); ?>" data-save-url="<?php echo e($attempt ? route('student.attempts.save', $attempt) : ''); ?>" autocomplete="off">
    <?php echo csrf_field(); ?>
    <?php $__currentLoopData = $questions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $q): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
            $d = $q->data;
            $r = $answers->get($q->id)?->response ?? [];
            $name = "answers[{$q->id}]";
            $shuffle = $quiz->shuffle_options;
        ?>
        <section class="question" id="q<?php echo e($q->id); ?>" data-question="<?php echo e($q->id); ?>">
            <div class="qhead"><span><strong>Pregunta <?php echo e($i + 1); ?></strong> de <?php echo e($questions->count()); ?></span><span><?php echo e(rtrim(rtrim(number_format($q->points, 2), '0'), '.')); ?> pt</span></div>
            <?php if($q->context): ?><div class="context"><?php echo e($q->context); ?></div><?php endif; ?>
            <div class="stem"><?php echo e($q->stem); ?></div>

            <?php switch($q->type):
                case (Question::SELECCION): ?>
                    <?php $opts = $shuffle ? Seeded::shuffle($d['options'], $seedBase.'q'.$q->id) : $d['options']; ?>
                    <?php $__currentLoopData = $opts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $o): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <label class="option">
                            <input type="radio" name="<?php echo e($name); ?>[choice]" value="<?php echo e($o['id']); ?>" <?php if(($r['choice'] ?? null) === $o['id']): echo 'checked'; endif; ?>>
                            <span class="letter"><?php echo e($letters[$k]); ?>.</span><span><?php echo e($o['text']); ?></span>
                        </label>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php break; ?>

                <?php case (Question::APAREAMIENTO): ?>
                    <?php
                        $choices = collect($d['pairs'])->map(fn ($p) => ['id' => $p['id'], 'text' => $p['right']])->concat($d['distractors'] ?? [])->all();
                        $choices = $shuffle ? Seeded::shuffle($choices, $seedBase.'m'.$q->id) : $choices;
                        $lefts = $shuffle ? Seeded::shuffle($d['pairs'], $seedBase.'l'.$q->id) : $d['pairs'];
                    ?>
                    <div class="context" style="border-color:var(--verde);white-space:normal"><strong>Columna B</strong>
                        <?php $__currentLoopData = $choices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><div><?php echo e($letters[$k]); ?>. <?php echo e($c['text']); ?></div><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                    <?php $__currentLoopData = $lefts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $n => $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="match-row">
                            <div><?php echo e($n + 1); ?>. <?php echo e($p['left']); ?></div>
                            <select name="<?php echo e($name); ?>[matches][<?php echo e($p['id']); ?>]" aria-label="Relación para <?php echo e($p['left']); ?>">
                                <option value="">— Seleccione —</option>
                                <?php $__currentLoopData = $choices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($c['id']); ?>" <?php if(($r['matches'][$p['id']] ?? null) === $c['id']): echo 'selected'; endif; ?>><?php echo e($letters[$k]); ?>. <?php echo e(\Illuminate\Support\Str::limit($c['text'], 60)); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php break; ?>

                <?php case (Question::COMPLETAR): ?>
                    <?php
                        $bank = null;
                        if (! empty($d['word_bank'])) {
                            $bank = collect($d['blanks'])->map(fn ($b) => $b[0])->concat($d['distractors'] ?? [])->unique()->values()->all();
                            $bank = Seeded::shuffle($bank, $seedBase.'b'.$q->id);
                        }
                    ?>
                    <?php if($bank): ?><p class="small muted">Banco de palabras: <?php echo e(implode(' · ', $bank)); ?></p><?php endif; ?>
                    <div class="fill-text">
                        <?php $__currentLoopData = \App\Services\QuestionBuilder::segments($d['text']); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $seg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php if(isset($seg['blank'])): ?>
                                <?php if($bank): ?>
                                    <select name="<?php echo e($name); ?>[blanks][<?php echo e($seg['blank']); ?>]" aria-label="Espacio <?php echo e($seg['blank'] + 1); ?>">
                                        <option value="">______</option>
                                        <?php $__currentLoopData = $bank; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $w): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($w); ?>" <?php if(($r['blanks'][$seg['blank']] ?? '') === $w): echo 'selected'; endif; ?>><?php echo e($w); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                <?php else: ?>
                                    <input type="text" name="<?php echo e($name); ?>[blanks][<?php echo e($seg['blank']); ?>]" value="<?php echo e($r['blanks'][$seg['blank']] ?? ''); ?>" maxlength="200" aria-label="Espacio <?php echo e($seg['blank'] + 1); ?>" spellcheck="false">
                                <?php endif; ?>
                            <?php else: ?><?php echo e($seg['text']); ?><?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                    <?php break; ?>

                <?php case (Question::LIBRE): ?>
                    <textarea name="<?php echo e($name); ?>[text]" maxlength="<?php echo e($d['max_chars']); ?>" data-counter rows="6" aria-label="Respuesta"><?php echo e($r['text'] ?? ''); ?></textarea>
                    <div class="counter"><span data-count><?php echo e(mb_strlen($r['text'] ?? '')); ?></span> / <?php echo e($d['max_chars']); ?> caracteres</div>
                    <?php break; ?>
            <?php endswitch; ?>
        </section>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

    <?php if (! ($preview)): ?>
        <div class="card">
            <p class="muted">Revise sus respuestas antes de enviar. Una vez enviada no podrá modificarla.</p>
            <button class="btn btn-ok" type="submit">Enviar evaluación</button>
        </div>
    <?php endif; ?>
</form>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(function () {
  var form = document.getElementById('quiz-form');
  var preview = <?php echo e($preview ? 'true' : 'false'); ?>;
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
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/runner/work/portal-docente-udc/portal-docente-udc/src/resources/views/student/attempts/take.blade.php ENDPATH**/ ?>