
<?php
    $d = $q->data;
    $resp = $answer?->response ?? [];
    $letters = range('A', 'Z');
    $norm = fn ($s) => \App\Services\QuizGrader::normalize((string) $s);
?>
<?php if($q->context): ?><div class="context"><?php echo e($q->context); ?></div><?php endif; ?>
<div class="stem"><?php echo e($q->stem); ?></div>

<?php switch($q->type):
    case (\App\Models\Question::SELECCION): ?>
        <?php $__currentLoopData = $d['options']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $o): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                $chosen = ($resp['choice'] ?? null) === $o['id'];
                $isCorrect = $o['id'] === $d['correct'];
                $cls = $reveal && $isCorrect ? 'correct' : ($reveal && $showResponse && $chosen && ! $isCorrect ? 'wrong' : '');
            ?>
            <div class="option <?php echo e($cls); ?>">
                <span class="letter"><?php echo e($letters[$k]); ?>.</span>
                <span><?php echo e($o['text']); ?>

                    <?php if($showResponse && $chosen): ?> <em class="small">← respuesta del estudiante</em><?php endif; ?>
                    <?php if($reveal && $isCorrect): ?> <strong class="small result-ok">✓ clave</strong><?php endif; ?>
                </span>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php if($showResponse && empty($resp['choice'])): ?><p class="small muted">Sin responder.</p><?php endif; ?>
        <?php break; ?>

    <?php case (\App\Models\Question::APAREAMIENTO): ?>
        <?php $rights = collect($d['pairs'])->pluck('right', 'id')->merge(collect($d['distractors'] ?? [])->pluck('text', 'id')); ?>
        <div class="table-wrap"><table>
            <thead><tr><th>Premisa</th><?php if($showResponse): ?><th>Respuesta del estudiante</th><?php endif; ?> <?php if($reveal): ?><th>Correcta</th><?php endif; ?></tr></thead>
            <tbody>
            <?php $__currentLoopData = $d['pairs']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php $given = $resp['matches'][$p['id']] ?? null; $ok = $given === $p['id']; ?>
                <tr>
                    <td><?php echo e($p['left']); ?></td>
                    <?php if($showResponse): ?><td class="<?php echo e($reveal ? ($ok ? 'result-ok' : 'result-bad') : ''); ?>"><?php echo e($given ? ($rights[$given] ?? '—') : 'Sin responder'); ?> <?php if($reveal): ?><?php echo e($ok ? '✓' : '✗'); ?><?php endif; ?></td><?php endif; ?>
                    <?php if($reveal): ?><td><?php echo e($p['right']); ?></td><?php endif; ?>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table></div>
        <?php if($reveal && ! empty($d['distractors'])): ?><p class="small muted">Distractores: <?php echo e(collect($d['distractors'])->pluck('text')->join(' · ')); ?></p><?php endif; ?>
        <?php break; ?>

    <?php case (\App\Models\Question::COMPLETAR): ?>
        <div class="fill-text">
            <?php $__currentLoopData = \App\Services\QuestionBuilder::segments($d['text']); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $seg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if(isset($seg['blank'])): ?>
                    <?php
                        $i = $seg['blank']; $given = $resp['blanks'][$i] ?? '';
                        $ok = $given !== '' && in_array($norm($given), array_map($norm, $d['blanks'][$i]), true);
                    ?>
                    <?php if($showResponse): ?>
                        <span class="badge <?php echo e($reveal ? ($ok ? 'badge-ok' : 'badge-err') : ''); ?>"><?php echo e($given !== '' ? $given : '(vacío)'); ?></span>
                    <?php endif; ?>
                    <?php if($reveal && (! $showResponse || ! $ok)): ?>
                        <span class="badge"><?php echo e(implode(' / ', $d['blanks'][$i])); ?></span>
                    <?php endif; ?>
                <?php else: ?><?php echo e($seg['text']); ?><?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php if($reveal && ! empty($d['word_bank'])): ?><p class="small muted">Con banco de palabras<?php echo e(! empty($d['distractors']) ? ' · distractores: '.implode(', ', $d['distractors']) : ''); ?>.</p><?php endif; ?>
        <?php break; ?>

    <?php case (\App\Models\Question::LIBRE): ?>
        <p class="small muted">Respuesta libre · máximo <?php echo e($d['max_chars']); ?> caracteres · calificación manual.</p>
        <?php if($showResponse): ?>
            <div class="context" style="border-color:var(--suave)"><?php echo e(($resp['text'] ?? '') !== '' ? $resp['text'] : 'Sin respuesta.'); ?></div>
            <p class="small muted"><?php echo e(mb_strlen($resp['text'] ?? '')); ?> / <?php echo e($d['max_chars']); ?> caracteres</p>
        <?php endif; ?>
        <?php if($reveal && ! empty($d['reference'])): ?><div class="small"><strong>Respuesta de referencia:</strong> <span class="prose"><?php echo e($d['reference']); ?></span></div><?php endif; ?>
        <?php break; ?>
<?php endswitch; ?>

<?php if($reveal && $q->feedback): ?>
    <div class="alert alert-ok small" style="margin-top:12px"><strong>Retroalimentación:</strong> <span class="prose"><?php echo e($q->feedback); ?></span></div>
<?php endif; ?>
<?php /**PATH /home/runner/work/portal-docente-udc/portal-docente-udc/src/resources/views/partials/question-review.blade.php ENDPATH**/ ?>