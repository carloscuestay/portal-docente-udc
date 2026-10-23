<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attempt extends Model
{
    public const EN_CURSO = 'en_curso';
    public const ENVIADO = 'enviado';     // tiene respuestas abiertas pendientes de calificar
    public const CALIFICADO = 'calificado';

    protected $fillable = [
        'quiz_id', 'user_id', 'number', 'question_order', 'started_at', 'deadline_at',
        'submitted_at', 'status', 'score', 'max_score', 'grade', 'ip',
    ];

    protected $casts = [
        'question_order' => 'array',
        'started_at' => 'datetime',
        'deadline_at' => 'datetime',
        'submitted_at' => 'datetime',
        'score' => 'float',
        'max_score' => 'float',
        'grade' => 'float',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    public function isInProgress(): bool
    {
        return $this->status === self::EN_CURSO;
    }

    /** ¿Se venció el tiempo (con 2 minutos de gracia por latencia)? */
    public function isExpired(int $graceSeconds = 0): bool
    {
        return $this->deadline_at && now()->gt($this->deadline_at->copy()->addSeconds($graceSeconds));
    }

    public function secondsLeft(): ?int
    {
        return $this->deadline_at ? max(0, now()->diffInSeconds($this->deadline_at, false)) : null;
    }
}
