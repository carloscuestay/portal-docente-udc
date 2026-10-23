<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\Question;
use App\Models\Quiz;
use App\Services\QuizGrader;
use Illuminate\Http\Request;

class GradingController extends Controller
{
    public function __construct(private QuizGrader $grader) {}

    public function results(Quiz $quiz)
    {
        $subject = $this->ownSubject($quiz->subject);
        $questions = $quiz->questions()->get();
        $attempts = $quiz->attempts()->with('answers')->get()->groupBy('user_id');
        $students = $subject->students()->wherePivot('is_active', true)->get();

        // Análisis de ítems: % promedio de acierto por pregunta (solo intentos finalizados)
        $finished = $quiz->attempts()->where('status', '!=', Attempt::EN_CURSO)->pluck('id');
        $itemStats = [];
        foreach ($questions as $q) {
            $answers = \App\Models\Answer::whereIn('attempt_id', $finished)->where('question_id', $q->id)->whereNotNull('score')->get();
            $itemStats[$q->id] = $answers->count() ? round($answers->avg('score') / max($q->points, 0.01) * 100) : null;
        }

        $grades = $quiz->attempts()->whereNotNull('grade')->where('status', '!=', Attempt::EN_CURSO)->get()
            ->groupBy('user_id')->map(fn ($a) => $a->max('grade'));

        return view('teacher.quizzes.results', compact('quiz', 'subject', 'questions', 'attempts', 'students', 'itemStats', 'grades'));
    }

    public function export(Quiz $quiz)
    {
        $subject = $this->ownSubject($quiz->subject);
        $students = $subject->students()->get();
        $attempts = $quiz->attempts()->where('status', '!=', Attempt::EN_CURSO)->get()->groupBy('user_id');

        $rows = [['Código', 'Nombre', 'Correo', 'Intentos', 'Mejor puntaje', 'Puntaje máximo', 'Nota (0-'.rtrim(rtrim(number_format($quiz->grade_scale, 1, '.', ''), '0'), '.').')', 'Estado']];
        foreach ($students as $s) {
            $list = $attempts->get($s->id, collect());
            $best = $list->sortByDesc('grade')->first();
            $rows[] = [
                $s->student_code, $s->name, $s->email, $list->count(),
                $best ? number_format($best->score, 2, ',', '') : '',
                $best ? number_format($best->max_score, 2, ',', '') : '',
                $best ? number_format($best->grade, 2, ',', '') : '',
                $best ? ($best->status === Attempt::CALIFICADO ? 'Calificado' : 'Pendiente revisión') : 'No presentó',
            ];
        }

        $out = "\xEF\xBB\xBF";
        foreach ($rows as $r) {
            $out .= implode(';', array_map(fn ($v) => '"'.str_replace('"', '""', (string) $v).'"', $r))."\r\n";
        }
        $name = 'notas_'.str($subject->code.'_'.$quiz->title)->slug('_').'.csv';

        return response($out, 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => "attachment; filename=\"{$name}\""]);
    }

    public function attempt(Attempt $attempt)
    {
        $this->ownSubject($attempt->quiz->subject);
        $attempt->load(['user', 'quiz', 'answers']);
        $questions = $attempt->quiz->questions()->get()->sortBy(fn ($q) => array_search($q->id, $attempt->question_order ?? []))->values();

        return view('teacher.attempts.show', [
            'attempt' => $attempt, 'quiz' => $attempt->quiz, 'questions' => $questions,
            'answers' => $attempt->answers->keyBy('question_id'),
        ]);
    }

    /** Califica (o ajusta) manualmente las respuestas del intento. */
    public function grade(Request $request, Attempt $attempt)
    {
        $this->ownSubject($attempt->quiz->subject);
        abort_if($attempt->isInProgress(), 422, 'El intento aún está en curso.');
        $questions = $attempt->quiz->questions()->get()->keyBy('id');
        // Aceptar coma decimal (4,5)
        $request->merge(['scores' => array_map(fn ($v) => is_string($v) ? str_replace(',', '.', trim($v)) : $v, (array) $request->input('scores', []))]);
        $request->validate(['scores' => 'array', 'scores.*' => 'nullable|numeric|min:0', 'feedback' => 'array', 'feedback.*' => 'nullable|string|max:5000']);

        foreach ($attempt->answers as $answer) {
            /** @var Question|null $q */
            $q = $questions->get($answer->question_id);
            if (! $q) {
                continue;
            }
            $raw = $request->input("scores.{$q->id}");
            if ($raw !== null && $raw !== '') {
                $answer->score = min((float) $raw, $q->points);
            }
            $answer->teacher_feedback = $request->input("feedback.{$q->id}");
            $answer->save();
        }
        $this->grader->recalculate($attempt->fresh());

        return redirect()->route('teacher.attempts.show', $attempt)->with('ok', 'Calificación guardada.');
    }

    /** Elimina un intento (p. ej. para habilitar un nuevo intento por inconvenientes técnicos). */
    public function destroy(Attempt $attempt)
    {
        $quiz = $attempt->quiz;
        $this->ownSubject($quiz->subject);
        $attempt->delete();

        return redirect()->route('teacher.quizzes.results', $quiz)->with('ok', 'Intento eliminado: el estudiante puede presentar de nuevo.');
    }
}
