<?php $__env->startSection('title', 'Panel docente'); ?>
<?php $__env->startSection('content'); ?>
<div class="page-head">
    <div>
        <h1>Panel docente</h1>
        <p class="muted">Bienvenido, <?php echo e(auth()->user()->name); ?>.</p>
    </div>
    <a class="btn" href="<?php echo e(route('teacher.subjects.create')); ?>">+ Nueva asignatura</a>
</div>

<?php if($requests->isNotEmpty()): ?>
<div class="card">
    <h3>Solicitudes de acceso pendientes (<?php echo e($requests->count()); ?>)</h3>
    <ul class="list">
        <?php $__currentLoopData = $requests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li>
                <div><strong><?php echo e($r->name); ?></strong> · <?php echo e($r->email); ?> · Código <?php echo e($r->student_code); ?><br>
                    <span class="small muted"><?php echo e($r->subject->label); ?> · <?php echo e($r->created_at->diffForHumans()); ?></span></div>
                <div class="actions">
                    <form class="inline" method="POST" action="<?php echo e(route('teacher.requests.approve', $r)); ?>"><?php echo csrf_field(); ?><button class="btn btn-sm btn-ok">Aprobar</button></form>
                    <form class="inline" method="POST" action="<?php echo e(route('teacher.requests.reject', $r)); ?>"><?php echo csrf_field(); ?><button class="btn btn-sm btn-danger">Rechazar</button></form>
                </div>
            </li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>
</div>
<?php endif; ?>

<?php if($pendingGrading->isNotEmpty()): ?>
<div class="card">
    <h3>Respuestas abiertas por calificar</h3>
    <ul class="list">
        <?php $__currentLoopData = $pendingGrading; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li><span><?php echo e($a->user->name); ?> · <span class="muted"><?php echo e($a->quiz->title); ?> (<?php echo e($a->quiz->subject->code); ?>)</span></span>
                <a class="btn btn-sm" href="<?php echo e(route('teacher.attempts.show', $a)); ?>">Calificar</a></li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>
</div>
<?php endif; ?>

<h2>Mis asignaturas</h2>
<?php $__empty_1 = true; $__currentLoopData = $subjects->groupBy('program'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $program => $list): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <h3 class="muted" style="margin-top:20px"><?php echo e($program); ?></h3>
    <div class="grid">
        <?php $__currentLoopData = $list; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a class="card" href="<?php echo e(route('teacher.subjects.show', $s)); ?>" style="text-decoration:none;color:inherit">
                <div class="actions" style="justify-content:space-between">
                    <span class="badge"><?php echo e($s->code); ?><?php echo e($s->group ? ' · G'.$s->group : ''); ?></span>
                    <span class="badge <?php echo e($s->is_active ? 'badge-ok' : 'badge-off'); ?>"><?php echo e($s->is_active ? $s->period : 'Inactiva'); ?></span>
                </div>
                <h3 style="margin-top:10px"><?php echo e($s->name); ?></h3>
                <div class="stats">
                    <span><?php echo e($s->enrollments_count); ?> estudiantes</span>
                    <span><?php echo e($s->materials_count); ?> materiales</span>
                    <span><?php echo e($s->quizzes_count); ?> evaluaciones</span>
                </div>
            </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <div class="card empty">Aún no ha creado asignaturas. <a href="<?php echo e(route('teacher.subjects.create')); ?>">Cree la primera</a>.</div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/runner/work/portal-docente-udc/portal-docente-udc/src/resources/views/teacher/dashboard.blade.php ENDPATH**/ ?>