<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    public const SELECCION = 'seleccion_multiple';
    public const APAREAMIENTO = 'apareamiento';
    public const COMPLETAR = 'completar';
    public const LIBRE = 'respuesta_libre';

    public const TYPES = [
        self::SELECCION => 'Selección múltiple con única respuesta',
        self::APAREAMIENTO => 'Apareamiento (relacionar columnas)',
        self::COMPLETAR => 'Completar frases',
        self::LIBRE => 'Respuesta libre (caracteres limitados)',
    ];

    protected $fillable = ['quiz_id', 'type', 'context', 'stem', 'data', 'points', 'position', 'feedback'];

    protected $casts = ['data' => 'array', 'points' => 'float'];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function isManual(): bool
    {
        return $this->type === self::LIBRE;
    }
}
