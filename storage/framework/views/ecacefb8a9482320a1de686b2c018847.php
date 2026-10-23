<?php $__env->startSection('title', 'Resultados · '.$quiz->title); ?>
<?php $__env->startSection('content'); ?>
<div class="breadcrumb"><a href="<?php echo e(route('dashboard')); ?>">Inicio</a> / <a href="<?php echo e(route('teacher.subjects.show', $subject)); ?>"><?php echo e($subject->code); ?></a> / <a href="<?php echo e(route('teacher.quizzes.show', $quiz)); ?>"><?php echo e($quiz->title); ?></a> / Resultados</div>
<div class="page-head">
    <div><h1>Resultados</h1><p class="muted"><?php echo e($quiz->title); ?> · nota sobre <?php echo e($quiz->grade_scale); ?></p></div>
    <a class="btn" href="<?php echo e(route('teacher.quizzes.export', $quiz)); ?>">Descargar notas (CSV/Excel)</a>
</div>

<?php
    $presented = $grades->count();
    $avg = $presented ? round($grades->avg(), 2) : null;
    $passing = $grades->filter(fn ($g) => $g >= $quiz->grade_scale * 0.6)->count();
?>
<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(170px,1fr))">
    <div class="card"><div class="muted small">Presentaron</div><div class="big-grade"><?php echo e($presented); ?>/<?php echo e($students->count()); ?></div></div>
    <div class="card"><div class="muted small">Promedio</div><div class="big-grade"><?php echo e($avg ?? '—'); ?></div></div>
    <div class="card"><div class="muted small">Aprobaron (≥ 60 %)</div><div class="big-grade"><?php echo e($passing); ?></div></div>
</div>

<h2>Por estudiante</h2>
<div class="table-wrap">
<table>
    <thead><tr><th>Estudiante</th><th>Intentos</th><th>Mejor nota</th><th>Estado</th><th></th></tr></thead>
    <tbody>
    <?php $__currentLoopData = $students; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php $list = $attempts->get($s->id, collect())->sortBy('number'); ?>
        <tr>
            <td><?php echo e($s->name); ?><br><span class="small muted"><?php echo e($s->student_code); ?> · <?php echo e($s->email); ?></span></td>
            <td><?php echo e($list->count()); ?></td>
            <td><strong><?php echo e(isset($grades[$s->id]) ? number_format($grades[$s->id], 2) : '—'); ?></strong></td>
            <td>
                <?php $__empty_1 = true; $__currentLoopData = $list; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="small">#<?php echo e($a->number); ?>:
                        <?php if($a->status === 'en_curso'): ?><span class="badge badge-warn">En curso</span>
                        <?php elseif($a->status === 'enviado'): ?><span class="badge badge-warn">Por calificar</span>
                        <?php else: ?><span class="badge badge-ok">Calificado</span><?php endif; ?>
                        <?php echo e($a->grade !== null ? number_format($a->grade, 2) : ''); ?>

                        <a href="<?php echo e(route('teacher.attempts.show', $a)); ?>">ver</a>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <span class="badge badge-off">No ha presentado</span>
                <?php endif; ?>
            </td>
            <td></td>
        </tr>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </tbody>
</table>
</div>

<h2>Análisis por pregunta</h2>
<p class="small muted">Porcentaje promedio de acierto. Valores muy bajos pueden indicar una pregunta difícil, ambigua o con clave errada.</p>
<div class="table-wrap">
<table>
    <thead><tr><th>#</th><th>Pregunta</th><th>Tipo</th><th>% acierto</th></tr></thead>
    <tbody>
    <?php $__currentLoopData = $questions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $q): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <tr>
            <td><?php echo e($i + 1); ?></td>
            <td><?php echo e(\Illuminate\Support\Str::limit($q->stem, 110)); ?></td>
            <td class="small"><?php echo e($q->typeLabel()); ?></td>
            <td>
                <?php if($itemStats[$q->id] === null): ?> — <?php else: ?>
                    <span class="badge <?php echo e($itemStats[$q->id] >= 60 ? 'badge-ok' : ($itemStats[$q->id] >= 30 ? 'badge-warn' : 'badge-err')); ?>"><?php echo e($itemStats[$q->id]); ?> %</span>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </tbody>
</table>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/runner/work/portal-docente-udc/portal-docente-udc/src/resources/views/teacher/quizzes/results.blade.php ENDPATH**/ ?>