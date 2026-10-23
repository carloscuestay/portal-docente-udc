<?php $__env->startSection('title', $quiz->title); ?>
<?php $__env->startSection('content'); ?>
<div class="breadcrumb"><a href="<?php echo e(route('dashboard')); ?>">Inicio</a> / <a href="<?php echo e(route('student.subjects.show', $quiz->subject)); ?>"><?php echo e($quiz->subject->code); ?></a> / Evaluación</div>
<h1><?php echo e($quiz->title); ?></h1>
<p class="muted"><?php echo e($quiz->subject->name); ?></p>

<div class="card">
    <div class="row">
        <div><div class="muted small">Preguntas</div><strong><?php echo e($quiz->questions_count); ?></strong></div>
        <div><div class="muted small">Tiempo</div><strong><?php echo e($quiz->time_limit_minutes ? $quiz->time_limit_minutes.' minutos' : 'Sin límite'); ?></strong></div>
        <div><div class="muted small">Intentos</div><strong><?php echo e($attempts->count()); ?> de <?php echo e($quiz->max_attempts); ?></strong></div>
        <div><div class="muted small">Cierra</div><strong><?php echo e($quiz->closes_at?->format('d/m/Y h:i a') ?? 'Sin fecha'); ?></strong></div>
    </div>
    <?php if($quiz->instructions): ?><hr style="border:0;border-top:1px solid var(--borde);margin:16px 0"><div class="prose"><?php echo e($quiz->instructions); ?></div><?php endif; ?>
</div>

<?php if($inProgress): ?>
    <div class="alert alert-warn">Tiene un intento en curso.
        <?php if($inProgress->deadline_at): ?> El tiempo sigue corriendo: termina a las <?php echo e($inProgress->deadline_at->format('h:i a')); ?>.<?php endif; ?></div>
    <a class="btn" href="<?php echo e(route('student.attempts.take', $inProgress)); ?>">Continuar evaluación</a>
<?php elseif($canStart): ?>
    <div class="alert alert-warn small">
        Al iniciar comenzará a correr el tiempo<?php echo e($quiz->time_limit_minutes ? ' ('.$quiz->time_limit_minutes.' minutos)' : ''); ?>.
        Sus respuestas se guardan automáticamente; si se cierra el navegador puede volver a entrar y continuar mientras haya tiempo.
        Al agotarse el tiempo la evaluación se envía con lo guardado.
    </div>
    <form method="POST" action="<?php echo e(route('student.quizzes.start', $quiz)); ?>" onsubmit="return confirm('¿Iniciar la evaluación ahora?')">
        <?php echo csrf_field(); ?> <button class="btn" type="submit"><?php echo e($attempts->isEmpty() ? 'Iniciar evaluación' : 'Iniciar nuevo intento'); ?></button>
    </form>
<?php elseif(! $quiz->isOpen()): ?>
    <div class="alert alert-warn">
        <?php if($quiz->opens_at && now()->lt($quiz->opens_at)): ?> La evaluación se habilita el <?php echo e($quiz->opens_at->format('d/m/Y \a \l\a\s h:i a')); ?>.
        <?php else: ?> La evaluación está cerrada. <?php endif; ?>
    </div>
<?php else: ?>
    <div class="alert alert-warn">Ya utilizó todos los intentos permitidos.</div>
<?php endif; ?>

<?php if($attempts->where('status', '!=', 'en_curso')->isNotEmpty()): ?>
    <h2>Mis intentos</h2>
    <div class="table-wrap"><table>
        <thead><tr><th>#</th><th>Enviado</th><th>Nota</th><th></th></tr></thead>
        <tbody>
        <?php $__currentLoopData = $attempts->where('status', '!=', 'en_curso'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr><td><?php echo e($a->number); ?></td><td><?php echo e($a->submitted_at?->format('d/m/Y h:i a')); ?></td>
                <td><?php echo e($a->status === 'calificado' ? number_format($a->grade, 2).' / '.$quiz->grade_scale : 'Pendiente de revisión'); ?></td>
                <td><a href="<?php echo e(route('student.attempts.result', $a)); ?>">Ver detalle</a></td></tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table></div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/runner/work/portal-docente-udc/portal-docente-udc/src/resources/views/student/quizzes/show.blade.php ENDPATH**/ ?>