<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\Attempt;
use App\Models\Question;
use Illuminate\Support\Str;

/**
 * Calificación automática de las preguntas cerradas y consolidación de la nota del intento.
 * Las preguntas de respuesta libre quedan pendientes (score = null) hasta que el docente las califique.
 */
class QuizGrader
{
    /** Puntaje obtenido en una pregunta para una respuesta dada; null si requiere calificación manual. */
    public function score(Question $q, ?array $response): ?float
    {
        if ($q->isManual()) {
            $text = trim((string) ($response['text'] ?? ''));

            return $text === '' ? 0.0 : null; // en blanco = 0 automáticamente
        }

        return round($q->points * $this->ratio($q, $response ?? []), 2);
    }

    /** Proporción de acierto entre 0 y 1. */
    public function ratio(Question $q, array $response): float
    {
        $d = $q->data;

        switch ($q->type) {
            case Question::SELECCION:
                return ($response['choice'] ?? null) === $d['correct'] ? 1.0 : 0.0;

            case Question::APAREAMIENTO:
                $pairs = $d['pairs'] ?? [];
                if (! $pairs) {
                    return 0.0;
                }
                $ok = 0;
                foreach ($pairs as $p) {
                    if (($response['matches'][$p['id']] ?? null) === $p['id']) {
                        $ok++;
                    }
                }

                return $ok / count($pairs);

            case Question::COMPLETAR:
                $blanks = $d['blanks'] ?? [];
                if (! $blanks) {
                    return 0.0;
                }
                $ok = 0;
                foreach ($blanks as $i => $accepted) {
                    $given = self::normalize((string) ($response['blanks'][$i] ?? ''));
                    if ($given !== '' && in_array($given, array_map([self::class, 'normalize'], $accepted), true)) {
                        $ok++;
                    }
                }

                return $ok / count($blanks);
        }

        return 0.0;
    }

    /** Comparación tolerante: sin tildes, minúsculas, espacios y puntuación final normalizados. */
    public static function normalize(string $s): string
    {
        $s = Str::lower(Str::ascii(trim($s)));
        $s = preg_replace('/\s+/', ' ', $s);

        return trim($s, " .,;:!?¡¿\"'");
    }

    /** Califica todas las respuestas del intento y recalcula totales. */
    public function gradeAttempt(Attempt $attempt): Attempt
    {
        $questions = $attempt->quiz->questions()->get()->keyBy('id');
        $answers = $attempt->answers()->get()->keyBy('question_id');

        foreach ($questions as $q) {
            $answer = $answers->get($q->id) ?? new Answer(['attempt_id' => $attempt->id, 'question_id' => $q->id, 'response' => null]);
            // No sobrescribir la calificación manual ya asignada por el docente.
            if (! ($q->isManual() && $answer->exists && $answer->score !== null && trim((string) ($answer->response['text'] ?? '')) !== '')) {
                $answer->score = $this->score($q, $answer->response);
            }
            $answer->save();
        }

        return $this->recalculate($attempt);
    }

    public function recalculate(Attempt $attempt): Attempt
    {
        $quiz = $attempt->quiz;
        $answers = $attempt->answers()->get();
        $max = (float) $quiz->questions()->sum('points');
        $score = (float) $answers->sum(fn ($a) => (float) $a->score);
        $pending = $answers->contains(fn ($a) => $a->score === null);

        $attempt->max_score = $max;
        $attempt->score = round($score, 2);
        $attempt->grade = $max > 0 ? round($score / $max * $quiz->grade_scale, 2) : 0;
        $attempt->status = $pending ? Attempt::ENVIADO : Attempt::CALIFICADO;
        $attempt->save();

        return $attempt;
    }
}
