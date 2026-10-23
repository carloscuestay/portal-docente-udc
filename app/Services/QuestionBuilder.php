<?php

namespace App\Services;

use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Convierte los campos del formulario del docente en la estructura JSON
 * ("data") que se guarda para cada tipo de pregunta.
 */
class QuestionBuilder
{
    public const BLANK_PATTERN = '/\[\[(.+?)\]\]/u';

    public function build(Request $request): array
    {
        return match ($request->input('type')) {
            Question::SELECCION => $this->multipleChoice($request),
            Question::APAREAMIENTO => $this->matching($request),
            Question::COMPLETAR => $this->fillBlank($request),
            Question::LIBRE => $this->openText($request),
            default => throw ValidationException::withMessages(['type' => 'Tipo de pregunta no válido.']),
        };
    }

    private function multipleChoice(Request $request): array
    {
        $texts = array_values(array_filter(array_map('trim', (array) $request->input('options', [])), fn ($t) => $t !== ''));
        if (count($texts) < 2) {
            throw ValidationException::withMessages(['options' => 'Escriba al menos dos opciones.']);
        }
        $correct = (int) $request->input('correct', -1);
        $raw = (array) $request->input('options', []);
        // El índice "correct" se refiere a la posición en el formulario: recalcular tras filtrar vacíos.
        $correctText = isset($raw[$correct]) ? trim($raw[$correct]) : '';
        if ($correctText === '') {
            throw ValidationException::withMessages(['correct' => 'Marque la opción correcta (no puede estar vacía).']);
        }
        $options = [];
        $correctId = null;
        foreach ($texts as $i => $text) {
            $id = 'o'.($i + 1);
            $options[] = ['id' => $id, 'text' => $text];
            if ($correctId === null && $text === $correctText) {
                $correctId = $id;
            }
        }

        return ['options' => $options, 'correct' => $correctId];
    }

    private function matching(Request $request): array
    {
        $left = (array) $request->input('left', []);
        $right = (array) $request->input('right', []);
        $pairs = [];
        foreach ($left as $i => $l) {
            $l = trim((string) $l);
            $r = trim((string) ($right[$i] ?? ''));
            if ($l === '' && $r === '') {
                continue;
            }
            if ($l === '' || $r === '') {
                throw ValidationException::withMessages(['left' => 'Cada par debe tener premisa y respuesta.']);
            }
            $pairs[] = ['id' => 'p'.(count($pairs) + 1), 'left' => $l, 'right' => $r];
        }
        if (count($pairs) < 2) {
            throw ValidationException::withMessages(['left' => 'Agregue al menos dos pares.']);
        }
        $distractors = [];
        foreach ($this->lines($request->input('distractors')) as $i => $d) {
            $distractors[] = ['id' => 'd'.($i + 1), 'text' => $d];
        }

        return ['pairs' => $pairs, 'distractors' => $distractors];
    }

    private function fillBlank(Request $request): array
    {
        $text = trim((string) $request->input('text'));
        preg_match_all(self::BLANK_PATTERN, $text, $m);
        if (empty($m[1])) {
            throw ValidationException::withMessages(['text' => 'Marque al menos un espacio con [[respuesta]]. Use | para respuestas alternativas: [[Cartagena|Cartagena de Indias]].']);
        }
        $blanks = array_map(fn ($b) => array_values(array_filter(array_map('trim', explode('|', $b)), fn ($x) => $x !== '')), $m[1]);

        return [
            'text' => $text,
            'blanks' => $blanks,
            'word_bank' => $request->boolean('word_bank'),
            'distractors' => $this->lines($request->input('bank_distractors')),
        ];
    }

    private function openText(Request $request): array
    {
        $max = (int) $request->input('max_chars', 500);
        if ($max < 20 || $max > 10000) {
            throw ValidationException::withMessages(['max_chars' => 'El límite de caracteres debe estar entre 20 y 10.000.']);
        }

        return [
            'max_chars' => $max,
            'reference' => trim((string) $request->input('reference')) ?: null,
        ];
    }

    private function lines(?string $value): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) $value)), fn ($x) => $x !== ''));
    }

    /** Divide el texto de "completar" en segmentos [texto, índice de espacio, texto, ...]. */
    public static function segments(string $text): array
    {
        $parts = preg_split(self::BLANK_PATTERN, $text);
        $out = [];
        foreach ($parts as $i => $p) {
            $out[] = ['text' => $p];
            if ($i < count($parts) - 1) {
                $out[] = ['blank' => $i];
            }
        }

        return $out;
    }
}
