<?php

namespace App\Services;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Importa una evaluación desde JSON.
 * Formato: { titulo, duracion_minutos, intentos_permitidos, escala{maxima}, barajar_preguntas, barajar_opciones,
 *            mostrar_retroalimentacion, instrucciones?, fuentes[], preguntas[] }
 * Tipos de pregunta: seleccion_unica | verdadero_falso | apareamiento | completar | respuesta_libre
 */
class QuizImporter
{
    public function import(Subject $subject, string $json, bool $publish = false): Quiz
    {
        $d = json_decode($json, true);
        if (! is_array($d) || empty($d['preguntas']) || ! is_array($d['preguntas'])) {
            throw ValidationException::withMessages(['json' => 'El archivo no es un JSON de evaluación válido (falta "preguntas").']);
        }

        return DB::transaction(function () use ($subject, $d, $publish) {
            $quiz = $subject->quizzes()->create([
                'title' => mb_substr($d['titulo'] ?? 'Evaluación importada', 0, 255),
                'instructions' => $this->instructions($d),
                'time_limit_minutes' => isset($d['duracion_minutos']) ? max(1, (int) $d['duracion_minutos']) : null,
                'max_attempts' => max(1, min(10, (int) ($d['intentos_permitidos'] ?? 1))),
                'grade_scale' => (float) ($d['escala']['maxima'] ?? 5.0),
                'shuffle_questions' => (bool) ($d['barajar_preguntas'] ?? true),
                'shuffle_options' => (bool) ($d['barajar_opciones'] ?? true),
                'show_results' => ($d['mostrar_retroalimentacion'] ?? 'al_finalizar') !== 'nunca',
                'is_published' => $publish,
            ]);

            foreach (array_values($d['preguntas']) as $i => $p) {
                $quiz->questions()->create($this->question($p, $i));
            }

            return $quiz;
        });
    }

    private function question(array $p, int $i): array
    {
        $n = $i + 1;
        $tipo = $p['tipo'] ?? 'seleccion_unica';
        $feedback = trim(($p['retroalimentacion'] ?? '').(! empty($p['fuente']) ? "\nFuente: ".$p['fuente'] : '')) ?: null;
        $base = [
            'context' => $p['contexto'] ?? null,
            'stem' => trim((string) ($p['enunciado'] ?? '')) ?: throw ValidationException::withMessages(['json' => "Pregunta {$n}: falta el enunciado."]),
            'points' => (float) ($p['puntaje'] ?? 1),
            'position' => $n,
            'feedback' => $feedback,
        ];

        switch ($tipo) {
            case 'seleccion_unica':
            case 'seleccion_multiple':
            case 'verdadero_falso':
                $opts = $p['opciones'] ?? ($tipo === 'verdadero_falso'
                    ? [['id' => 'v', 'texto' => 'Verdadero'], ['id' => 'f', 'texto' => 'Falso']] : []);
                if (count($opts) < 2) {
                    throw ValidationException::withMessages(['json' => "Pregunta {$n}: necesita al menos dos opciones."]);
                }
                $options = [];
                $correct = null;
                foreach (array_values($opts) as $k => $o) {
                    $id = 'o'.($k + 1);
                    $options[] = ['id' => $id, 'text' => (string) $o['texto']];
                    if ((string) ($o['id'] ?? '') === (string) ($p['respuesta_correcta'] ?? '')) {
                        $correct = $id;
                    }
                }
                if (! $correct) {
                    throw ValidationException::withMessages(['json' => "Pregunta {$n}: la respuesta correcta no coincide con ninguna opción."]);
                }
                $data = ['options' => $options, 'correct' => $correct];
                if ($tipo === 'verdadero_falso') {
                    $data['fixed_order'] = true; // Verdadero/Falso no se baraja
                }

                return $base + ['type' => Question::SELECCION, 'data' => $data];

            case 'apareamiento':
                $pairs = [];
                foreach (array_values($p['pares'] ?? []) as $k => $par) {
                    $pairs[] = ['id' => 'p'.($k + 1), 'left' => (string) $par['premisa'], 'right' => (string) $par['respuesta']];
                }
                if (count($pairs) < 2) {
                    throw ValidationException::withMessages(['json' => "Pregunta {$n}: el apareamiento necesita al menos dos pares."]);
                }
                $distractors = array_map(fn ($t, $k) => ['id' => 'd'.($k + 1), 'text' => (string) $t], $p['distractores'] ?? [], array_keys($p['distractores'] ?? []));

                return $base + ['type' => Question::APAREAMIENTO, 'data' => ['pairs' => $pairs, 'distractors' => $distractors]];

            case 'completar':
                $text = (string) ($p['texto'] ?? '');
                preg_match_all(QuestionBuilder::BLANK_PATTERN, $text, $m);
                if (empty($m[1])) {
                    throw ValidationException::withMessages(['json' => "Pregunta {$n}: el texto debe marcar los espacios con [[respuesta]]."]);
                }

                return $base + ['type' => Question::COMPLETAR, 'data' => [
                    'text' => $text,
                    'blanks' => array_map(fn ($b) => array_map('trim', explode('|', $b)), $m[1]),
                    'word_bank' => (bool) ($p['banco_palabras'] ?? false),
                    'distractors' => $p['distractores'] ?? [],
                ]];

            case 'respuesta_libre':
                return $base + ['type' => Question::LIBRE, 'data' => [
                    'max_chars' => max(20, min(10000, (int) ($p['max_caracteres'] ?? 500))),
                    'reference' => $p['respuesta_referencia'] ?? null,
                ]];
        }

        throw ValidationException::withMessages(['json' => "Pregunta {$n}: tipo «{$tipo}» no soportado."]);
    }

    private function instructions(array $d): string
    {
        $lines = [];
        if (! empty($d['instrucciones'])) {
            $lines[] = $d['instrucciones'];
        }
        $n = count($d['preguntas']);
        $lines[] = "La evaluación tiene {$n} preguntas.";
        if (! empty($d['duracion_minutos'])) {
            $lines[] = "Duración: {$d['duracion_minutos']} minutos. Al terminar el tiempo se envía automáticamente.";
        }
        if (isset($d['escala']['maxima'])) {
            $lines[] = 'Nota máxima '.number_format((float) $d['escala']['maxima'], 1)
                .(isset($d['escala']['aprobatoria']) ? '; aprueba con '.number_format((float) $d['escala']['aprobatoria'], 1) : '').'.';
        }
        $lines[] = 'Intentos permitidos: '.($d['intentos_permitidos'] ?? 1).'.';
        if (! empty($d['fuentes'])) {
            $lines[] = "\nFuentes:\n- ".implode("\n- ", $d['fuentes']);
        }

        return implode("\n", $lines);
    }
}
