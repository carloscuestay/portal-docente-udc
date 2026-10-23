<?php $__env->startSection('title', 'Intento · '.$attempt->user->name); ?>
<?php $__env->startSection('content'); ?>
<div class="breadcrumb"><a href="<?php echo e(route('dashboard')); ?>">Inicio</a> / <a href="<?php echo e(route('teacher.quizzes.results', $quiz)); ?>"><?php echo e($quiz->title); ?></a> / Intento</div>
<div class="page-head">
    <div>
        <h1><?php echo e($attempt->user->name); ?></h1>
        <p class="muted"><?php echo e($attempt->user->email); ?> · Intento #<?php echo e($attempt->number); ?> ·
            Inició <?php echo e($attempt->started_at->format('d/m/Y H:i')); ?>

            <?php if($attempt->submitted_at): ?> · Envió <?php echo e($attempt->submitted_at->format('d/m/Y H:i')); ?> (<?php echo e($attempt->started_at->diffInMinutes($attempt->submitted_at) < 1 ? '<1' : (int) $attempt->started_at->diffInMinutes($attempt->submitted_at)); ?> min)<?php endif; ?>
        </p>
    </div>
    <div class="card" style="margin:0;text-align:center;min-width:170px">
        <div class="muted small">Nota</div>
        <div class="big-grade"><?php echo e($attempt->grade !== null ? number_format($attempt->grade, 2) : '—'); ?></div>
        <div class="small muted"><?php echo e($attempt->score); ?> / <?php echo e($attempt->max_score); ?> pts</div>
    </div>
</div>

<?php if($attempt->isInProgress()): ?>
    <div class="alert alert-warn">El estudiante aún está presentando esta evaluación.</div>
<?php endif; ?>

<form method="POST" action="<?php echo e(route('teacher.attempts.grade', $attempt)); ?>">
    <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
    <?php $__currentLoopData = $questions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $q): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php $a = $answers->get($q->id); ?>
        <div class="question">
            <div class="qhead">
                <span><strong>Pregunta <?php echo e($i + 1); ?></strong> · <?php echo e($q->typeLabel()); ?></span>
                <span>
                    <?php if($a && $a->score === null): ?><span class="badge badge-warn">Por calificar</span><?php endif; ?>
                </span>
            </div>
            <?php echo $__env->make('partials.question-review', ['q' => $q, 'answer' => $a, 'reveal' => true, 'showResponse' => true], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php if (! ($attempt->isInProgress())): ?>
            <div class="row" style="margin-top:12px">
                <div><label for="s<?php echo e($q->id); ?>">Puntaje (máx. <?php echo e($q->points); ?>)</label>
                    <input id="s<?php echo e($q->id); ?>" type="number" step="0.01" min="0" max="<?php echo e($q->points); ?>" name="scores[<?php echo e($q->id); ?>]" value="<?php echo e($a?->score); ?>" <?php if($q->isManual() && $a && $a->score === null): ?> required <?php endif; ?>></div>
                <div style="grid-column:span 2"><label for="f<?php echo e($q->id); ?>">Comentario para el estudiante</label>
                    <input id="f<?php echo e($q->id); ?>" type="text" name="feedback[<?php echo e($q->id); ?>]" value="<?php echo e($a?->teacher_feedback); ?>" maxlength="5000"></div>
            </div>
            <?php endif; ?>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <?php if (! ($attempt->isInProgress())): ?>
        <div class="actions"><button class="btn" type="submit">Guardar calificación</button></div>
    <?php endif; ?>
</form>

<details class="card" style="margin-top:24px">
    <summary>Anular intento</summary>
    <p class="small muted">Elimina este intento y sus respuestas, permitiendo que el estudiante presente de nuevo (por ejemplo, por fallas de conexión).</p>
    <form method="POST" action="<?php echo e(route('teacher.attempts.destroy', $attempt)); ?>" onsubmit="return confirm('¿Anular el intento? Se perderán sus respuestas.')"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
        <button class="btn btn-danger">Anular intento</button></form>
</details>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/runner/work/portal-docente-udc/portal-docente-udc/src/resources/views/teacher/attempts/show.blade.php ENDPATH**/ ?>