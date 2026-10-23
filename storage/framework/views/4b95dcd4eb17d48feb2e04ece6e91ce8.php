<?php $__env->startSection('title', ($question->exists ? 'Editar' : 'Nueva').' pregunta'); ?>
<?php $__env->startSection('content'); ?>
<?php
    use App\Models\Question;
    $d = $question->data ?? [];
    $type = old('type', $question->type);
    $letters = range('A', 'Z');
?>
<div class="breadcrumb"><a href="<?php echo e(route('dashboard')); ?>">Inicio</a> / <a href="<?php echo e(route('teacher.subjects.show', $quiz->subject_id)); ?>"><?php echo e($quiz->subject->code); ?></a> / <a href="<?php echo e(route('teacher.quizzes.show', $quiz)); ?>"><?php echo e($quiz->title); ?></a> / Pregunta</div>
<h1><?php echo e($question->exists ? 'Editar pregunta' : 'Nueva pregunta'); ?></h1>
<p class="muted"><?php echo e(Question::TYPES[$type]); ?></p>

<form class="card" method="POST" action="<?php echo e($question->exists ? route('teacher.questions.update', $question) : route('teacher.questions.store', $quiz)); ?>">
    <?php echo csrf_field(); ?>
    <?php if($question->exists): ?> <?php echo method_field('PUT'); ?> <?php endif; ?>
    <input type="hidden" name="type" value="<?php echo e($type); ?>">

    <label for="context">Contexto / caso <span class="hint">(opcional; texto, situación o datos sobre los que se basa la pregunta, como en Saber Pro)</span></label>
    <textarea id="context" name="context"><?php echo e(old('context', $question->context)); ?></textarea>

    <label for="stem">Enunciado</label>
    <textarea id="stem" name="stem" required style="min-height:80px"><?php echo e(old('stem', $question->stem)); ?></textarea>

    <?php switch($type):
        case (Question::SELECCION): ?>
            <?php
                $opts = old('options', collect($d['options'] ?? [])->pluck('text')->all());
                $opts = array_pad($opts, 4, '');
                $correctIdx = old('correct', collect($d['options'] ?? [])->search(fn ($o) => $o['id'] === ($d['correct'] ?? null)));
            ?>
            <label>Opciones <span class="hint">(marque la correcta; las vacías se ignoran)</span></label>
            <div id="options">
                <?php $__currentLoopData = $opts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $o): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="repeat-item">
                        <input type="radio" name="correct" value="<?php echo e($i); ?>" <?php if((string) $correctIdx === (string) $i): echo 'checked'; endif; ?> title="Correcta" required>
                        <strong data-letter><?php echo e($letters[$i]); ?>.</strong>
                        <input type="text" name="options[]" value="<?php echo e($o); ?>" maxlength="2000">
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
            <button type="button" class="btn btn-sm btn-sec" data-add-option>+ Opción</button>
            <?php break; ?>

        <?php case (Question::APAREAMIENTO): ?>
            <?php
                $left = old('left', collect($d['pairs'] ?? [])->pluck('left')->all());
                $right = old('right', collect($d['pairs'] ?? [])->pluck('right')->all());
                $n = max(count($left), 4);
            ?>
            <label>Pares a relacionar <span class="hint">(columna A ↔ columna B; al estudiante se le muestran barajadas)</span></label>
            <div id="pairs">
                <?php for($i = 0; $i < $n; $i++): ?>
                    <div class="repeat-item">
                        <input type="text" name="left[]" value="<?php echo e($left[$i] ?? ''); ?>" placeholder="Premisa <?php echo e($i + 1); ?>" maxlength="1000">
                        <span>↔</span>
                        <input type="text" name="right[]" value="<?php echo e($right[$i] ?? ''); ?>" placeholder="Respuesta <?php echo e($i + 1); ?>" maxlength="1000">
                    </div>
                <?php endfor; ?>
            </div>
            <button type="button" class="btn btn-sm btn-sec" data-add-pair>+ Par</button>
            <label for="distractors">Distractores <span class="hint">(opcional; respuestas extra que no corresponden a ninguna premisa, una por línea)</span></label>
            <textarea id="distractors" name="distractors" style="min-height:70px"><?php echo e(old('distractors', collect($d['distractors'] ?? [])->pluck('text')->join("\n"))); ?></textarea>
            <p class="small muted">Calificación proporcional: cada par correcto suma la parte correspondiente del puntaje.</p>
            <?php break; ?>

        <?php case (Question::COMPLETAR): ?>
            <label for="text">Texto con espacios <span class="hint">Encierre cada respuesta en doble corchete. Use | para alternativas válidas.</span></label>
            <textarea id="text" name="text" required style="min-height:140px" placeholder="El patrón [[MVC|Modelo Vista Controlador]] separa la lógica en tres capas; la capa de [[vista]] presenta la información."><?php echo e(old('text', $d['text'] ?? '')); ?></textarea>
            <p class="small muted">La comparación ignora mayúsculas, tildes y espacios extra. Calificación proporcional por espacio.</p>
            <label class="check"><input type="checkbox" name="word_bank" value="1" <?php if(old('word_bank', $d['word_bank'] ?? false)): echo 'checked'; endif; ?>> Mostrar banco de palabras (el estudiante elige de una lista en lugar de escribir)</label>
            <label for="bank_distractors">Palabras distractoras para el banco <span class="hint">(una por línea)</span></label>
            <textarea id="bank_distractors" name="bank_distractors" style="min-height:70px"><?php echo e(old('bank_distractors', implode("\n", $d['distractors'] ?? []))); ?></textarea>
            <?php break; ?>

        <?php case (Question::LIBRE): ?>
            <div class="row">
                <div><label for="max_chars">Límite de caracteres</label>
                    <input id="max_chars" type="number" name="max_chars" min="20" max="10000" value="<?php echo e(old('max_chars', $d['max_chars'] ?? 500)); ?>" required></div>
            </div>
            <label for="reference">Respuesta de referencia / rúbrica <span class="hint">(solo la ve el docente; y el estudiante si se muestran resultados)</span></label>
            <textarea id="reference" name="reference"><?php echo e(old('reference', $d['reference'] ?? '')); ?></textarea>
            <?php break; ?>
    <?php endswitch; ?>

    <div class="row">
        <div><label for="points">Puntaje</label>
            <input id="points" type="number" step="0.1" min="0.1" max="100" name="points" value="<?php echo e(old('points', $question->points)); ?>" required></div>
    </div>
    <label for="feedback">Retroalimentación / justificación de la clave <span class="hint">(opcional)</span></label>
    <textarea id="feedback" name="feedback" style="min-height:70px"><?php echo e(old('feedback', $question->feedback)); ?></textarea>

    <div class="actions" style="margin-top:16px">
        <button class="btn" type="submit" name="after" value="volver">Guardar</button>
        <?php if (! ($question->exists)): ?>
            <button class="btn btn-sec" type="submit" name="after" value="otra">Guardar y agregar otra</button>
        <?php endif; ?>
        <a class="btn btn-sec" href="<?php echo e(route('teacher.quizzes.show', $quiz)); ?>">Cancelar</a>
    </div>
</form>

<?php $__env->startPush('scripts'); ?>
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
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/runner/work/portal-docente-udc/portal-docente-udc/src/resources/views/teacher/questions/form.blade.php ENDPATH**/ ?>