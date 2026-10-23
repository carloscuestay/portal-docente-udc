<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use App\Services\RosterImporter;
use Illuminate\Database\Seeder;

/** Datos de demostración (solo para desarrollo local): php artisan migrate --seed */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $domain = config('portal.institutional_domain');
        $teacher = User::updateOrCreate(['email' => 'docente@'.$domain], [
            'name' => 'Docente Demo', 'role' => User::DOCENTE, 'password' => 'docente-demo-123',
        ]);

        $subject = Subject::firstOrCreate(
            ['code' => 'IS-ADN2', 'period' => '2026-2', 'group' => '1'],
            ['teacher_id' => $teacher->id, 'name' => 'Analítica de Datos II', 'program' => 'Ingeniería de Sistemas',
                'description' => 'Curso demo para probar el portal.']
        );
        Subject::firstOrCreate(
            ['code' => 'AI-INF', 'period' => '2026-2', 'group' => '1'],
            ['teacher_id' => $teacher->id, 'name' => 'Informática Aplicada', 'program' => 'Administración Industrial']
        );

        app(RosterImporter::class)->import($subject, "codigo;nombre;correo\n0012345678;Estudiante Uno;estudiante1@{$domain}\n0012345679;Estudiante Dos;estudiante2@{$domain}\n");

        $subject->materials()->firstOrCreate(['title' => 'Programa del curso'], [
            'type' => 'texto', 'unit' => 'Unidad 1', 'body' => "Objetivos, contenidos y evaluación del curso.",
        ]);

        $quiz = $subject->quizzes()->firstOrCreate(['title' => 'Simulacro tipo Saber Pro'], [
            'instructions' => 'Lea cada pregunta con atención. Tiene 30 minutos.',
            'time_limit_minutes' => 30, 'max_attempts' => 1, 'is_published' => true, 'show_results' => true,
        ]);
        if ($quiz->questions()->doesntExist()) {
            $quiz->questions()->createMany([
                ['type' => Question::SELECCION, 'position' => 1, 'points' => 1,
                    'context' => 'Una empresa registra 1.200 ventas en enero y 1.500 en febrero.',
                    'stem' => '¿Cuál fue el crecimiento porcentual de las ventas?',
                    'data' => ['options' => [['id' => 'o1', 'text' => '20 %'], ['id' => 'o2', 'text' => '25 %'], ['id' => 'o3', 'text' => '30 %'], ['id' => 'o4', 'text' => '300 %']], 'correct' => 'o2'],
                    'feedback' => '(1500 − 1200) / 1200 = 0,25'],
                ['type' => Question::APAREAMIENTO, 'position' => 2, 'points' => 2,
                    'stem' => 'Relacione cada medida con su definición.',
                    'data' => ['pairs' => [
                        ['id' => 'p1', 'left' => 'Media', 'right' => 'Suma de valores dividida por su cantidad'],
                        ['id' => 'p2', 'left' => 'Mediana', 'right' => 'Valor central de los datos ordenados'],
                        ['id' => 'p3', 'left' => 'Moda', 'right' => 'Valor que más se repite'],
                    ], 'distractors' => [['id' => 'd1', 'text' => 'Diferencia entre el máximo y el mínimo']]]],
                ['type' => Question::COMPLETAR, 'position' => 3, 'points' => 2,
                    'stem' => 'Complete la frase.',
                    'data' => ['text' => 'En SQL, la cláusula [[WHERE]] filtra filas y [[GROUP BY]] agrupa resultados.', 'blanks' => [['WHERE'], ['GROUP BY']], 'word_bank' => false, 'distractors' => []]],
                ['type' => Question::LIBRE, 'position' => 4, 'points' => 3,
                    'stem' => 'Explique en máximo 300 caracteres qué es un modelo de datos.',
                    'data' => ['max_chars' => 300, 'reference' => 'Representación abstracta de la estructura, relaciones y restricciones de los datos.']],
            ]);
        }
    }
}
