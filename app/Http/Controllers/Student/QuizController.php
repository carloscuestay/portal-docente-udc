<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Attempt;
use App\Models\Question;
use App\Models\Quiz;
use App\Services\QuizGrader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuizController extends Controller
{
    /** Segundos de gracia por latencia de red al guardar/enviar después del tiempo límite. */
    private const GRACE = 90;

    public function __construct(private QuizGrader $grader) {}

    public function show(Request $request, Quiz $quiz)
    {
        $this->authorizeQuiz($request, $quiz);
        $attempts = $quiz->attempts()->where('user_id', $request->user()->id)->orderBy('number')->get();
        foreach ($attempts as $a) {
            $this->finalizeIfExpired($a);
        }

        return view('student.quizzes.show', [
            'quiz' => $quiz->loadCount('questions'),
            'attempts' => $attempts,
            'inProgress' => $attempts->firstWhere('status', Attempt::EN_CURSO),
            'canStart' => $quiz->isOpen() && $attempts->count() < $quiz->max_attempts,
        ]);
    }

    public function start(Request $request, Quiz $quiz)
    {
        $this->authorizeQuiz($request, $quiz);
        $user = $request->user();

        $attempt = DB::transaction(function () use ($quiz, $user, $request) {
            $existing = $quiz->attempts()->where('user_id', $user->id)->lockForUpdate()->get();
            if ($open = $existing->firstWhere('status', Attempt::EN_CURSO)) {
                return $open;
            }
            abort_unless($quiz->isOpen(), 403, 'La evaluación no está disponible en este momento.');
            abort_if($existing->count() >= $quiz->max_attempts, 403, 'Ya usó todos los intentos permitidos.');

            $ids = $quiz->questions()->pluck('id')->all();
            abort_if(empty($ids), 403, 'La evaluación no tiene preguntas.');
            if ($quiz->shuffle_questions) {
                shuffle($ids);
            }
            $now = now();
            $deadline = $quiz->time_limit_minutes ? $now->copy()->addMinutes($quiz->time_limit_minutes) : null;
            if ($quiz->closes_at && (! $deadline || $quiz->closes_at->lt($deadline))) {
                $deadline = $quiz->closes_at->copy();
            }

            return $quiz->attempts()->create([
                'user_id' => $user->id,
                'number' => $existing->count() + 1,
                'question_order' => $ids,
                'started_at' => $now,
                'deadline_at' => $deadline,
                'status' => Attempt::EN_CURSO,
                'ip' => $request->ip(),
            ]);
        });

        return redirect()->route('student.attempts.take', $attempt);
    }

    public function take(Request $request, Attempt $attempt)
    {
        $this->ownAttempt($request, $attempt);
        if ($this->finalizeIfExpired($attempt) || ! $attempt->isInProgress()) {
            return redirect()->route('student.attempts.result', $attempt);
        }

        $questions = Question::whereIn('id', $attempt->question_order)->get()
            ->sortBy(fn ($q) => array_search($q->id, $attempt->question_order))->values();

        return view('student.attempts.take', [
            'quiz' => $attempt->quiz,
            'attempt' => $attempt,
            'questions' => $questions,
            'answers' => $attempt->answers()->get()->keyBy('question_id'),
            'preview' => false,
        ]);
    }

    /** Autoguardado (AJAX) para no perder respuestas si se cae la conexión. */
    public function save(Request $request, Attempt $attempt)
    {
        $this->ownAttempt($request, $attempt);
        if (! $attempt->isInProgress() || $attempt->isExpired(self::GRACE)) {
            return response()->json(['ok' => false, 'message' => 'El tiempo terminó.'], 409);
        }
        $this->storeResponses($attempt, (array) $request->input('answers', []));

        return response()->json(['ok' => true, 'saved_at' => now()->format('H:i:s'), 'seconds_left' => $attempt->secondsLeft()]);
    }

    public function submit(Request $request, Attempt $attempt)
    {
        $this->ownAttempt($request, $attempt);
        if ($attempt->isInProgress()) {
            if (! $attempt->isExpired(self::GRACE)) {
                $this->storeResponses($attempt, (array) $request->input('answers', []));
            }
            $this->finish($attempt);
        }

        return redirect()->route('student.attempts.result', $attempt)->with('ok', 'Evaluación enviada correctamente.');
    }

    public function result(Request $request, Attempt $attempt)
    {
        $this->ownAttempt($request, $attempt);
        $this->finalizeIfExpired($attempt);
        if ($attempt->isInProgress()) {
            return redirect()->route('student.attempts.take', $attempt);
        }
        $quiz = $attempt->quiz;
        $questions = Question::whereIn('id', $attempt->question_order)->get()
            ->sortBy(fn ($q) => array_search($q->id, $attempt->question_order))->values();
        // Las respuestas correctas solo se muestran si el docente lo permite y la evaluación ya cerró
        // (para que no se filtren a quienes aún no presentan).
        $reveal = $quiz->show_results && (! $quiz->closes_at || now()->gt($quiz->closes_at));

        return view('student.attempts.result', [
            'attempt' => $attempt, 'quiz' => $quiz, 'questions' => $questions,
            'answers' => $attempt->answers()->get()->keyBy('question_id'), 'reveal' => $reveal,
        ]);
    }

    // ----------------------------------------------------------------

    private function authorizeQuiz(Request $request, Quiz $quiz): void
    {
        abort_unless($quiz->is_published && $request->user()->canAccessSubject($quiz->subject), 404);
    }

    private function ownAttempt(Request $request, Attempt $attempt): void
    {
        abort_unless($attempt->user_id === $request->user()->id, 404);
    }

    private function finalizeIfExpired(Attempt $attempt): bool
    {
        if ($attempt->isInProgress() && $attempt->isExpired(self::GRACE)) {
            $this->finish($attempt);

            return true;
        }

        return false;
    }

    private function finish(Attempt $attempt): void
    {
        DB::transaction(function () use ($attempt) {
            $attempt->submitted_at = now();
            $attempt->save();
            $this->grader->gradeAttempt($attempt);
        });
    }

    /** Sanitiza y guarda las respuestas según el tipo de cada pregunta. */
    private function storeResponses(Attempt $attempt, array $input): void
    {
        $questions = Question::whereIn('id', $attempt->question_order)->get()->keyBy('id');

        foreach ($input as $qid => $raw) {
            $q = $questions->get((int) $qid);
            if (! $q || ! is_array($raw)) {
                continue;
            }
            $response = match ($q->type) {
                Question::SELECCION => ['choice' => is_string($raw['choice'] ?? null) ? $raw['choice'] : null],
                Question::APAREAMIENTO => ['matches' => collect((array) ($raw['matches'] ?? []))
                    ->filter(fn ($v, $k) => is_string($k) && is_string($v) && $v !== '')->map(fn ($v) => mb_substr($v, 0, 20))->all()],
                Question::COMPLETAR => ['blanks' => collect((array) ($raw['blanks'] ?? []))
                    ->map(fn ($v) => mb_substr(trim((string) $v), 0, 200))->all()],
                Question::LIBRE => ['text' => mb_substr(str_replace("\r\n", "\n", (string) ($raw['text'] ?? '')), 0, (int) ($q->data['max_chars'] ?? 500))],
                default => null,
            };
            Answer::updateOrCreate(['attempt_id' => $attempt->id, 'question_id' => $q->id], ['response' => $response]);
        }
    }
}
