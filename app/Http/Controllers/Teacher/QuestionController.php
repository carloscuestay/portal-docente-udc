<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Quiz;
use App\Services\QuestionBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class QuestionController extends Controller
{
    public function __construct(private QuestionBuilder $builder) {}

    public function create(Request $request, Quiz $quiz)
    {
        $this->ownSubject($quiz->subject);
        $type = $request->query('tipo', Question::SELECCION);
        abort_unless(array_key_exists($type, Question::TYPES), 404);

        return view('teacher.questions.form', [
            'quiz' => $quiz,
            'question' => new Question(['type' => $type, 'points' => 1, 'data' => []]),
        ]);
    }

    public function store(Request $request, Quiz $quiz)
    {
        $this->ownSubject($quiz->subject);
        $data = $this->validated($request);
        $data['position'] = (int) $quiz->questions()->max('position') + 1;
        $quiz->questions()->create($data);

        $next = $request->input('after') === 'otra'
            ? redirect()->route('teacher.questions.create', [$quiz, 'tipo' => $data['type']])
            : redirect()->route('teacher.quizzes.show', $quiz);

        return $next->with('ok', 'Pregunta agregada.');
    }

    public function edit(Question $question)
    {
        $this->ownSubject($question->quiz->subject);

        return view('teacher.questions.form', ['quiz' => $question->quiz, 'question' => $question]);
    }

    public function update(Request $request, Question $question)
    {
        $this->ownSubject($question->quiz->subject);
        $question->update($this->validated($request));

        return redirect()->route('teacher.quizzes.show', $question->quiz_id)
            ->with('ok', 'Pregunta actualizada.'.($question->quiz->attempts()->exists() ? ' Nota: los intentos ya enviados conservan su calificación.' : ''));
    }

    public function destroy(Question $question)
    {
        $this->ownSubject($question->quiz->subject);
        $question->delete();

        return back()->with('ok', 'Pregunta eliminada.');
    }

    public function move(Question $question, string $direction)
    {
        $quiz = $question->quiz;
        $this->ownSubject($quiz->subject);
        $list = $quiz->questions()->get()->values();
        $i = $list->search(fn ($q) => $q->id === $question->id);
        $j = $direction === 'arriba' ? $i - 1 : $i + 1;
        if ($j >= 0 && $j < $list->count()) {
            $items = $list->all();
            [$items[$i], $items[$j]] = [$items[$j], $items[$i]];
            DB::transaction(function () use ($items) {
                foreach ($items as $pos => $q) {
                    $q->update(['position' => $pos + 1]);
                }
            });
        }

        return back();
    }

    private function validated(Request $request): array
    {
        $base = $request->validate([
            'type' => ['required', Rule::in(array_keys(Question::TYPES))],
            'context' => 'nullable|string|max:20000',
            'stem' => 'required|string|max:10000',
            'points' => 'required|numeric|min:0.1|max:100',
            'feedback' => 'nullable|string|max:5000',
        ]);
        $base['data'] = $this->builder->build($request);

        return $base;
    }
}
