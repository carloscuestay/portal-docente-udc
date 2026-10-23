<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Asignaturas (cursos) a cargo del docente
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name');
            $table->string('program', 120); // Ingeniería de Sistemas | Administración Industrial
            $table->string('period', 20);   // p. ej. 2026-2
            $table->string('group', 20)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['code', 'period', 'group']);
        });

        // Matrícula: lista de estudiantes autorizados por asignatura
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['subject_id', 'user_id']);
        });

        // Solicitudes de acceso de estudiantes que no estaban en la lista
        Schema::create('access_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('name');
            $table->string('student_code', 30)->nullable();
            $table->string('status', 20)->default('pendiente'); // pendiente | aprobada | rechazada
            $table->timestamps();
            $table->unique(['subject_id', 'email']);
        });

        // Material de clase
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('unit', 120)->nullable(); // unidad / semana
            $table->text('description')->nullable();
            $table->string('type', 20); // archivo | enlace | texto
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('url', 1000)->nullable();
            $table->longText('body')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        // Evaluaciones en línea tipo Saber
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->timestamp('opens_at')->nullable();
            $table->timestamp('closes_at')->nullable();
            $table->unsignedInteger('time_limit_minutes')->nullable();
            $table->unsignedTinyInteger('max_attempts')->default(1);
            $table->boolean('shuffle_questions')->default(true);
            $table->boolean('shuffle_options')->default(true);
            $table->boolean('show_results')->default(false); // mostrar respuestas correctas al finalizar
            $table->decimal('grade_scale', 4, 1)->default(5.0); // nota máxima (escala 0-5)
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            // seleccion_multiple | apareamiento | completar | respuesta_libre
            $table->string('type', 30);
            $table->text('context')->nullable(); // contexto / caso (estilo Saber Pro)
            $table->text('stem');                // enunciado
            $table->json('data');                // opciones, pares, espacios, límite de caracteres
            $table->decimal('points', 6, 2)->default(1);
            $table->unsignedInteger('position')->default(0);
            $table->text('feedback')->nullable(); // retroalimentación / justificación
            $table->timestamps();
        });

        Schema::create('attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('number')->default(1);
            $table->json('question_order');
            $table->timestamp('started_at');
            $table->timestamp('deadline_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->string('status', 20)->default('en_curso'); // en_curso | enviado | calificado
            $table->decimal('score', 8, 2)->nullable();
            $table->decimal('max_score', 8, 2)->nullable();
            $table->decimal('grade', 4, 2)->nullable(); // nota en escala del quiz
            $table->string('ip', 45)->nullable();
            $table->timestamps();
            $table->unique(['quiz_id', 'user_id', 'number']);
        });

        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->json('response')->nullable();
            $table->decimal('score', 6, 2)->nullable(); // null = pendiente de calificación manual
            $table->text('teacher_feedback')->nullable();
            $table->timestamps();
            $table->unique(['attempt_id', 'question_id']);
        });
    }

    public function down(): void
    {
        foreach (['answers', 'attempts', 'questions', 'quizzes', 'materials', 'access_requests', 'enrollments', 'subjects'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
