<?php

namespace App\Http\Controllers;

use App\Models\AccessRequest;
use App\Models\Attempt;
use App\Models\Quiz;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        if ($user->isTeacher()) {
            $subjects = $user->taughtSubjects()->withCount(['enrollments', 'materials', 'quizzes'])
                ->orderByDesc('is_active')->orderByDesc('period')->orderBy('name')->get();
            $requests = AccessRequest::with('subject')->where('status', 'pendiente')
                ->whereIn('subject_id', $subjects->pluck('id'))->latest()->get();
            $pendingGrading = Attempt::with(['quiz.subject', 'user'])->where('status', Attempt::ENVIADO)
                ->whereHas('quiz', fn ($q) => $q->whereIn('subject_id', $subjects->pluck('id')))
                ->latest('submitted_at')->limit(20)->get();

            return view('teacher.dashboard', compact('subjects', 'requests', 'pendingGrading'));
        }

        $subjects = $user->activeSubjects()->with('teacher')->orderBy('name')->get();
        $openQuizzes = Quiz::with('subject')->whereIn('subject_id', $subjects->pluck('id'))
            ->where('is_published', true)
            ->where(fn ($q) => $q->whereNull('opens_at')->orWhere('opens_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('closes_at')->orWhere('closes_at', '>=', now()))
            ->orderBy('closes_at')->get();

        return view('student.dashboard', compact('subjects', 'openQuizzes'));
    }
}
