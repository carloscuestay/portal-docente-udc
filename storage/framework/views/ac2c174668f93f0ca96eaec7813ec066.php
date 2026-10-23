<?php if(session('ok')): ?>
    <div class="alert alert-ok" role="status"><?php echo e(session('ok')); ?></div>
<?php endif; ?>
<?php if(session('import_errors')): ?>
    <div class="alert alert-warn">
        <strong>Líneas no procesadas (<?php echo e(count(session('import_errors'))); ?>):</strong>
        <ul><?php $__currentLoopData = array_slice(session('import_errors'), 0, 30); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($e); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul>
    </div>
<?php endif; ?>
<?php if($errors->any()): ?>
    <div class="alert alert-error" role="alert">
        <strong>Revise la información:</strong>
        <ul><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($e); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul>
    </div>
<?php endif; ?>
<?php /**PATH /home/runner/work/portal-docente-udc/portal-docente-udc/src/resources/views/partials/flash.blade.php ENDPATH**/ ?>