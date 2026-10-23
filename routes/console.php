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
