<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Importa la lista de estudiantes (CSV o texto pegado) y los matricula en la asignatura.
 * Formato por línea: codigo;nombre completo;correo  (separador ; , o tabulador; orden flexible).
 * Solo se aceptan correos del dominio institucional configurado.
 */
class RosterImporter
{
    public function domain(): string
    {
        return Str::lower(config('portal.institutional_domain'));
    }

    public function isInstitutional(string $email): bool
    {
        return Str::endsWith(Str::lower(trim($email)), '@'.$this->domain());
    }

    /** @return array{created:int, enrolled:int, already:int, errors:array<int,string>} */
    public function import(Subject $subject, string $content): array
    {
        $result = ['created' => 0, 'enrolled' => 0, 'already' => 0, 'errors' => []];
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content); // BOM de Excel
        if (! mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }

        foreach (preg_split('/\r\n|\r|\n/', $content) as $n => $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $cols = array_map(fn ($c) => trim($c, " \t\"'"), preg_split('/[;\t,]/', $line));
            $email = null;
            $code = null;
            $nameParts = [];
            foreach ($cols as $c) {
                if ($c === '') {
                    continue;
                }
                if (str_contains($c, '@')) {
                    $email = Str::lower($c);
                } elseif ($code === null && preg_match('/^\d{3,15}$/', $c)) {
                    $code = $c;
                } else {
                    $nameParts[] = $c;
                }
            }
            if (! $email) {
                // Encabezado u otra línea sin correo: se ignora si parece encabezado.
                if ($n > 0 || ! preg_match('/correo|email|nombre/i', $line)) {
                    $result['errors'][] = 'Línea '.($n + 1).': sin correo.';
                }
                continue;
            }
            if (! filter_var($email, FILTER_VALIDATE_EMAIL) || ! $this->isInstitutional($email)) {
                $result['errors'][] = 'Línea '.($n + 1).": {$email} no es un correo @".$this->domain().'.';
                continue;
            }

            $user = User::where('email', $email)->first();
            if ($user && $user->isTeacher()) {
                $result['errors'][] = 'Línea '.($n + 1).": {$email} pertenece a un docente.";
                continue;
            }
            if (! $user) {
                $user = User::create([
                    'name' => $nameParts ? Str::title(Str::lower(implode(' ', $nameParts))) : Str::before($email, '@'),
                    'email' => $email,
                    'student_code' => $code,
                    'program' => $subject->program,
                    'role' => User::ESTUDIANTE,
                ]);
                $result['created']++;
            } elseif ($code && ! $user->student_code) {
                $user->update(['student_code' => $code]);
            }

            $enrollment = Enrollment::firstOrNew(['subject_id' => $subject->id, 'user_id' => $user->id]);
            if ($enrollment->exists && $enrollment->is_active) {
                $result['already']++;
            } else {
                $enrollment->is_active = true;
                $enrollment->save();
                $result['enrolled']++;
            }
        }

        return $result;
    }
}
