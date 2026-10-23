<?php $__env->startSection('title', $subject->name); ?>
<?php $__env->startSection('content'); ?>
<div class="breadcrumb"><a href="<?php echo e(route('dashboard')); ?>">Inicio</a> / <?php echo e($subject->code); ?></div>
<h1><?php echo e($subject->name); ?></h1>
<p class="muted"><?php echo e($subject->program); ?> · <?php echo e($subject->code); ?><?php echo e($subject->group ? ' · Grupo '.$subject->group : ''); ?> · <?php echo e($subject->period); ?> · Prof. <?php echo e($subject->teacher->name); ?></p>
<?php if($subject->description): ?><div class="card prose"><?php echo e($subject->description); ?></div><?php endif; ?>

<h2>Evaluaciones</h2>
<div class="table-wrap">
<table>
    <thead><tr><th>Evaluación</th><th>Disponible</th><th>Mi nota</th><th></th></tr></thead>
    <tbody>
    <?php $__empty_1 = true; $__currentLoopData = $quizzes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $q): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php $mine = $myAttempts->get($q->id, collect()); $best = $mine->whereNotNull('grade')->max('grade'); ?>
        <tr>
            <td><strong><?php echo e($q->title); ?></strong><br><span class="small muted"><?php echo e($q->questions_count); ?> preguntas<?php echo e($q->time_limit_minutes ? ' · '.$q->time_limit_minutes.' min' : ''); ?></span></td>
            <td class="small">
                <?php echo e($q->opens_at?->format('d/m/Y H:i') ?? 'Desde ya'); ?> – <?php echo e($q->closes_at?->format('d/m/Y H:i') ?? 'sin cierre'); ?><br>
                <span class="badge <?php echo e($q->isOpen() ? 'badge-ok' : 'badge-off'); ?>"><?php echo e($q->statusLabel()); ?></span>
            </td>
            <td><?php echo e($best !== null ? number_format($best, 2).' / '.$q->grade_scale : ($mine->isNotEmpty() ? 'En revisión' : '—')); ?></td>
            <td><a class="btn btn-sm <?php echo e($q->isOpen() ? '' : 'btn-sec'); ?>" href="<?php echo e(route('student.quizzes.show', $q)); ?>"><?php echo e($q->isOpen() ? 'Abrir' : 'Ver'); ?></a></td>
        </tr>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr><td colspan="4" class="empty">No hay evaluaciones publicadas.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>

<h2>Material de clase</h2>
<?php $__empty_1 = true; $__currentLoopData = $materials; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit => $items): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <div class="card">
        <h3><?php echo e($unit); ?></h3>
        <ul class="list">
            <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li>
                    <div style="flex:1;min-width:240px">
                        <strong><?php echo e($m->title); ?></strong>
                        <?php if($m->description): ?><div class="small muted"><?php echo e($m->description); ?></div><?php endif; ?>
                        <?php if($m->type === 'texto'): ?>
                            <details style="margin-top:6px"><summary>Leer</summary><div class="prose" style="margin-top:8px"><?php echo e($m->body); ?></div></details>
                        <?php endif; ?>
                    </div>
                    <div class="actions">
                        <?php if($m->type === 'archivo'): ?>
                            <?php if(str_ends_with(strtolower($m->file_name), '.pdf')): ?>
                                <a class="btn btn-sm btn-sec" href="<?php echo e(route('materials.download', [$m, 'ver' => 1])); ?>" target="_blank">Ver</a>
                            <?php endif; ?>
                            <a class="btn btn-sm" href="<?php echo e(route('materials.download', $m)); ?>">Descargar <span class="small">(<?php echo e($m->humanSize()); ?>)</span></a>
                        <?php elseif($m->type === 'enlace'): ?>
                            <a class="btn btn-sm" href="<?php echo e($m->url); ?>" target="_blank" rel="noopener">Abrir enlace ↗</a>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    </div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <div class="card empty">Aún no hay material publicado.</div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/runner/work/portal-docente-udc/portal-docente-udc/src/resources/views/student/subjects/show.blade.php ENDPATH**/ ?>