<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

// Crea o actualiza la cuenta del docente (con contraseña de respaldo).
// Uso: php artisan portal:docente correo@dominio "Nombre Completo"
Artisan::command('portal:docente {email} {name} {--password=}', function (string $email, string $name) {
    $password = $this->option('password') ?: $this->secret('Contraseña (mínimo 10 caracteres)');
    if (! $password || strlen($password) < 10) {
        $this->error('La contraseña debe tener al menos 10 caracteres.');

        return 1;
    }
    $user = User::updateOrCreate(
        ['email' => Str::lower($email)],
        ['name' => $name, 'role' => User::DOCENTE, 'password' => $password, 'is_active' => true]
    );
    $this->info("Docente listo: {$user->email} (id {$user->id}).");
})->purpose('Crear o actualizar la cuenta del docente');

// Instalación/actualización en hosting sin SSH (se ejecuta desde un Cron Job de Hostinger):
// aplica migraciones y, si no existe, crea la cuenta docente con TEACHER_EMAILS + TEACHER_INITIAL_PASSWORD.
Artisan::command('portal:install', function () {
    $this->call('migrate', ['--force' => true]);
    $email = config('portal.teacher_emails')[0] ?? null;
    $password = env('TEACHER_INITIAL_PASSWORD');
    if ($email && $password && ! User::where('email', $email)->exists()) {
        User::create(['name' => env('TEACHER_NAME', 'Docente'), 'email' => $email, 'role' => User::DOCENTE, 'password' => $password]);
        $this->info("Cuenta docente creada: {$email}");
    }
    $this->call('optimize:clear');
    $this->info('Instalación verificada.');
})->purpose('Migrar la base de datos y crear la cuenta docente inicial');
