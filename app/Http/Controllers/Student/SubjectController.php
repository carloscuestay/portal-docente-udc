<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function show(Request $request, Subject $subject)
    {
        abort_unless($request->user()->canAccessSubject($subject), 403, 'No está habilitado en esta asignatura.');
        $materials = $subject->materials()->where('is_published', true)->get()->groupBy(fn ($m) => $m->unit ?: 'General');
        $quizzes = $subject->quizzes()->where('is_published', true)->withCount('questions')->get();
        $myAttempts = $request->user()->attempts()->whereIn('quiz_id', $quizzes->pluck('id'))->get()->groupBy('quiz_id');

        return view('student.subjects.show', compact('subject', 'materials', 'quizzes', 'myAttempts'));
    }
}
