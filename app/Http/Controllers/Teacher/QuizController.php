<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class QuizController extends Controller
{
    public function create(Subject $subject)
    {
        $this->ownSubject($subject);

        return view('teacher.quizzes.form', ['subject' => $subject, 'quiz' => new Quiz([
            'max_attempts' => 1, 'shuffle_questions' => true, 'shuffle_options' => true,
            'grade_scale' => 5.0, 'time_limit_minutes' => 60,
        ])]);
    }

    public function store(Request $request, Subject $subject)
    {
        $this->ownSubject($subject);
        $quiz = $subject->quizzes()->create($this->validated($request));

        return redirect()->route('teacher.quizzes.show', $quiz)->with('ok', 'Evaluación creada. Agregue las preguntas.');
    }

    /** Importa una evaluación desde un archivo JSON. */
    public function import(Request $request, Subject $subject, \App\Services\QuizImporter $importer)
    {
        $this->ownSubject($subject);
        $request->validate(['json' => 'required|file|max:2048']);
        $quiz = $importer->import($subject, (string) file_get_contents($request->file('json')->getRealPath()));

        return redirect()->route('teacher.quizzes.show', $quiz)
            ->with('ok', 'Evaluación importada con '.$quiz->questions()->count().' preguntas. Revise la vista previa, configure fechas y publíquela.');
    }

    public function show(Quiz $quiz)
    {
        $this->ownSubject($quiz->subject);
        $quiz->load('questions')->loadCount('attempts');

        return view('teacher.quizzes.show', ['quiz' => $quiz, 'subject' => $quiz->subject]);
    }

    public function edit(Quiz $quiz)
    {
        return view('teacher.quizzes.form', ['subject' => $this->ownSubject($quiz->subject), 'quiz' => $quiz]);
    }

    public function update(Request $request, Quiz $quiz)
    {
        $this->ownSubject($quiz->subject);
        $data = $this->validated($request);
        if ($data['is_published'] && ! $quiz->questions()->exists()) {
            return back()->withInput()->withErrors(['is_published' => 'No se puede publicar una evaluación sin preguntas.']);
        }
        $quiz->update($data);

        return redirect()->route('teacher.quizzes.show', $quiz)->with('ok', 'Evaluación actualizada.');
    }

    public function destroy(Quiz $quiz)
    {
        $subject = $this->ownSubject($quiz->subject);
        $quiz->delete();

        return redirect()->route('teacher.subjects.show', $subject)->with('ok', 'Evaluación eliminada.');
    }

    /** Copia la evaluación con sus preguntas (útil para otro grupo o periodo). */
    public function duplicate(Request $request, Quiz $quiz)
    {
        $this->ownSubject($quiz->subject);
        $target = Subject::findOrFail($request->input('subject_id', $quiz->subject_id));
        $this->ownSubject($target);

        $copy = DB::transaction(function () use ($quiz, $target) {
            $copy = $quiz->replicate(['is_published']);
            $copy->subject_id = $target->id;
            $copy->title = $quiz->title.' (copia)';
            $copy->is_published = false;
            $copy->save();
            foreach ($quiz->questions as $q) {
                $copy->questions()->create($q->only(['type', 'context', 'stem', 'data', 'points', 'position', 'feedback']));
            }

            return $copy;
        });

        return redirect()->route('teacher.quizzes.show', $copy)->with('ok', 'Evaluación duplicada como borrador.');
    }

    public function preview(Quiz $quiz)
    {
        $this->ownSubject($quiz->subject);

        return view('student.attempts.take', [
            'quiz' => $quiz, 'attempt' => null, 'questions' => $quiz->questions()->get(),
            'answers' => collect(), 'preview' => true,
        ]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'instructions' => 'nullable|string|max:10000',
            'opens_at' => 'nullable|date',
            'closes_at' => 'nullable|date|after:opens_at',
            'time_limit_minutes' => 'nullable|integer|min:1|max:600',
            'max_attempts' => 'required|integer|min:1|max:10',
            'grade_scale' => 'required|numeric|min:1|max:100',
        ], ['closes_at.after' => 'El cierre debe ser posterior a la apertura.']);

        foreach (['opens_at', 'closes_at'] as $f) {
            // Las fechas del formulario se interpretan en hora de Colombia.
            $data[$f] = $data[$f] ? Carbon::parse($data[$f], config('app.timezone')) : null;
        }
        foreach (['shuffle_questions', 'shuffle_options', 'show_results', 'is_published'] as $f) {
            $data[$f] = $request->boolean($f);
        }

        return $data;
    }
}
