<?php $__env->startSection('title', $quiz->title); ?>
<?php $__env->startSection('content'); ?>
<?php $st = $quiz->statusLabel(); $total = $quiz->questions->sum('points'); ?>
<div class="breadcrumb"><a href="<?php echo e(route('dashboard')); ?>">Inicio</a> / <a href="<?php echo e(route('teacher.subjects.show', $subject)); ?>"><?php echo e($subject->code); ?></a> / Evaluación</div>
<div class="page-head">
    <div>
        <h1><?php echo e($quiz->title); ?></h1>
        <div class="muted small">
            <span class="badge <?php echo e(['Abierta' => 'badge-ok', 'Programada' => 'badge-warn', 'Borrador' => 'badge-off', 'Cerrada' => 'badge-err'][$st]); ?>"><?php echo e($st); ?></span>
            <?php echo e($quiz->questions->count()); ?> preguntas · <?php echo e(rtrim(rtrim(number_format($total, 2), '0'), '.')); ?> puntos ·
            <?php echo e($quiz->time_limit_minutes ? $quiz->time_limit_minutes.' min' : 'Sin límite de tiempo'); ?> ·
            <?php echo e($quiz->max_attempts); ?> intento(s) ·
            <?php echo e($quiz->opens_at?->format('d/m/Y H:i') ?? 'sin apertura'); ?> → <?php echo e($quiz->closes_at?->format('d/m/Y H:i') ?? 'sin cierre'); ?>

        </div>
    </div>
    <div class="actions">
        <a class="btn btn-sec" href="<?php echo e(route('teacher.quizzes.edit', $quiz)); ?>">Configurar / publicar</a>
        <a class="btn btn-sec" href="<?php echo e(route('teacher.quizzes.preview', $quiz)); ?>" target="_blank">Vista previa</a>
        <a class="btn" href="<?php echo e(route('teacher.quizzes.results', $quiz)); ?>">Resultados (<?php echo e($quiz->attempts_count); ?>)</a>
    </div>
</div>

<?php if(! $quiz->is_published && $quiz->questions->isNotEmpty()): ?>
    <div class="alert alert-warn">La evaluación está en borrador. Cuando termine, vaya a <a href="<?php echo e(route('teacher.quizzes.edit', $quiz)); ?>">Configurar / publicar</a>.</div>
<?php endif; ?>
<?php if($quiz->attempts_count > 0): ?>
    <div class="alert alert-warn">Ya hay intentos registrados. Si modifica preguntas, los intentos enviados conservan su calificación.</div>
<?php endif; ?>

<div class="card">
    <h3>Agregar pregunta</h3>
    <div class="actions">
        <?php $__currentLoopData = \App\Models\Question::TYPES; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a class="btn btn-sec btn-sm" href="<?php echo e(route('teacher.questions.create', [$quiz, 'tipo' => $k])); ?>">+ <?php echo e($label); ?></a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>

<?php $__empty_1 = true; $__currentLoopData = $quiz->questions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $q): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <div class="question">
        <div class="qhead">
            <span><strong>Pregunta <?php echo e($i + 1); ?></strong> · <?php echo e($q->typeLabel()); ?> · <?php echo e(rtrim(rtrim(number_format($q->points, 2), '0'), '.')); ?> pt</span>
            <span class="actions no-print">
                <form class="inline" method="POST" action="<?php echo e(route('teacher.questions.move', [$q, 'arriba'])); ?>"><?php echo csrf_field(); ?><button class="btn btn-sm btn-sec" title="Subir" <?php if($loop->first): echo 'disabled'; endif; ?>>↑</button></form>
                <form class="inline" method="POST" action="<?php echo e(route('teacher.questions.move', [$q, 'abajo'])); ?>"><?php echo csrf_field(); ?><button class="btn btn-sm btn-sec" title="Bajar" <?php if($loop->last): echo 'disabled'; endif; ?>>↓</button></form>
                <a class="btn btn-sm btn-sec" href="<?php echo e(route('teacher.questions.edit', $q)); ?>">Editar</a>
                <form class="inline" method="POST" action="<?php echo e(route('teacher.questions.destroy', $q)); ?>" onsubmit="return confirm('¿Eliminar la pregunta?')"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?><button class="btn btn-sm btn-danger">Eliminar</button></form>
            </span>
        </div>
        <?php echo $__env->make('partials.question-review', ['q' => $q, 'answer' => null, 'reveal' => true, 'showResponse' => false], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <div class="card empty">Aún no hay preguntas. Use los botones de arriba para agregarlas.</div>
<?php endif; ?>

<details class="card">
    <summary>Duplicar evaluación</summary>
    <p class="small muted">Crea una copia en borrador (con todas las preguntas) en esta u otra de sus asignaturas.</p>
    <form method="POST" action="<?php echo e(route('teacher.quizzes.duplicate', $quiz)); ?>" class="actions">
        <?php echo csrf_field(); ?>
        <select name="subject_id" style="max-width:420px">
            <?php $__currentLoopData = auth()->user()->taughtSubjects()->orderByDesc('period')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($s->id); ?>" <?php if($s->id === $quiz->subject_id): echo 'selected'; endif; ?>><?php echo e($s->label); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <button class="btn btn-sec">Duplicar</button>
    </form>
</details>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/runner/work/portal-docente-udc/portal-docente-udc/src/resources/views/teacher/quizzes/show.blade.php ENDPATH**/ ?>