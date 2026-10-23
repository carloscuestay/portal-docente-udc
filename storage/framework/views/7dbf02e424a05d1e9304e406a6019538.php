<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'Inicio'); ?> · <?php echo e(config('app.name')); ?></title>
    <link rel="stylesheet" href="<?php echo e(asset('css/app.css')); ?>?v=1">
</head>
<body>
<header class="topbar">
    <div class="container">
        <a class="brand" href="<?php echo e(auth()->check() ? route('dashboard') : route('home')); ?>">
            <?php echo e(config('app.name')); ?>

            <small><?php echo e(config('portal.institution')); ?></small>
        </a>
        <nav>
            <?php if(auth()->guard()->check()): ?>
                <a href="<?php echo e(route('dashboard')); ?>">Inicio</a>
                <?php if(auth()->user()->isTeacher()): ?>
                    <a href="<?php echo e(route('teacher.subjects.create')); ?>">+ Asignatura</a>
                <?php endif; ?>
                <span class="small" style="padding:0 8px">
                    <?php if(auth()->user()->avatar_url): ?><img class="avatar" src="<?php echo e(auth()->user()->avatar_url); ?>" alt="" referrerpolicy="no-referrer"><?php endif; ?>
                    <?php echo e(\Illuminate\Support\Str::words(auth()->user()->name, 2, '')); ?>

                </span>
                <form method="POST" action="<?php echo e(route('logout')); ?>" class="inline"><?php echo csrf_field(); ?><button type="submit">Salir</button></form>
            <?php else: ?>
                <a href="<?php echo e(route('login')); ?>">Ingresar</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main>
    <div class="container">
        <?php echo $__env->make('partials.flash', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo $__env->yieldContent('content'); ?>
    </div>
</main>
<footer>
    <?php echo e(config('app.name')); ?> · <?php echo e(config('portal.institution')); ?> · <?php echo e(now()->year); ?>

</footer>
<?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH /home/runner/work/portal-docente-udc/portal-docente-udc/src/resources/views/layouts/app.blade.php ENDPATH**/ ?>