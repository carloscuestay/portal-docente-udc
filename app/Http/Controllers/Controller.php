<?php

namespace App\Http\Controllers;

use App\Models\Subject;

abstract class Controller
{
    /** Verifica que la asignatura pertenezca al docente autenticado. */
    protected function ownSubject(Subject $subject): Subject
    {
        abort_unless($subject->teacher_id === auth()->id(), 403, 'Esta asignatura no le pertenece.');

        return $subject;
    }
}
