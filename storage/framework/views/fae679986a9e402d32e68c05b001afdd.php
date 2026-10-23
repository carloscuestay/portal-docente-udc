<?php $__env->startSection('title', 'Resultado · '.$quiz->title); ?>
<?php $__env->startSection('content'); ?>
<div class="breadcrumb"><a href="<?php echo e(route('dashboard')); ?>">Inicio</a> / <a href="<?php echo e(route('student.quizzes.show', $quiz)); ?>"><?php echo e($quiz->title); ?></a> / Resultado</div>
<div class="page-head">
    <div>
        <h1><?php echo e($quiz->title); ?></h1>
        <p class="muted">Intento #<?php echo e($attempt->number); ?> · enviado <?php echo e($attempt->submitted_at?->format('d/m/Y h:i a')); ?></p>
    </div>
    <div class="card" style="margin:0;text-align:center;min-width:180px">
        <?php if($attempt->status === 'calificado'): ?>
            <div class="muted small">Nota</div>
            <div class="big-grade"><?php echo e(number_format($attempt->grade, 2)); ?></div>
            <div class="small muted">de <?php echo e($quiz->grade_scale); ?> · <?php echo e($attempt->score); ?> / <?php echo e($attempt->max_score); ?> pts</div>
        <?php else: ?>
            <div class="muted small">Estado</div>
            <div style="font-weight:700;color:var(--ambar)">Pendiente de revisión</div>
            <div class="small muted">Puntaje parcial: <?php echo e($attempt->score); ?> / <?php echo e($attempt->max_score); ?></div>
        <?php endif; ?>
    </div>
</div>

<?php if(! $reveal): ?>
    <div class="alert alert-ok">Su evaluación fue recibida. <?php echo e($quiz->show_results ? 'Las respuestas correctas se mostrarán cuando cierre la evaluación.' : ''); ?></div>
<?php endif; ?>

<?php $__currentLoopData = $questions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $q): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php $a = $answers->get($q->id); ?>
    <div class="question">
        <div class="qhead">
            <span><strong>Pregunta <?php echo e($i + 1); ?></strong></span>
            <span>
                <?php if(! $reveal): ?>
                <?php elseif($a?->score === null): ?> <span class="badge badge-warn">Por calificar</span>
                <?php else: ?> <strong><?php echo e(rtrim(rtrim(number_format($a->score, 2), '0'), '.') ?: '0'); ?></strong> / <?php echo e(rtrim(rtrim(number_format($q->points, 2), '0'), '.')); ?> pt <?php endif; ?>
            </span>
        </div>
        <?php echo $__env->make('partials.question-review', ['q' => $q, 'answer' => $a, 'reveal' => $reveal, 'showResponse' => true], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php if($a?->teacher_feedback): ?>
            <div class="alert alert-ok small" style="margin-top:10px"><strong>Comentario del docente:</strong> <?php echo e($a->teacher_feedback); ?></div>
        <?php endif; ?>
    </div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/runner/work/portal-docente-udc/portal-docente-udc/src/resources/views/student/attempts/result.blade.php ENDPATH**/ ?>