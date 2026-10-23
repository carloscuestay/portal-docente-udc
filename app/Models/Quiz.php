<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    protected $fillable = [
        'subject_id', 'title', 'instructions', 'opens_at', 'closes_at', 'time_limit_minutes',
        'max_attempts', 'shuffle_questions', 'shuffle_options', 'show_results', 'grade_scale', 'is_published',
    ];

    protected $casts = [
        'opens_at' => 'datetime',
        'closes_at' => 'datetime',
        'shuffle_questions' => 'boolean',
        'shuffle_options' => 'boolean',
        'show_results' => 'boolean',
        'is_published' => 'boolean',
        'grade_scale' => 'float',
    ];

    protected $attributes = [
        'max_attempts' => 1, 'shuffle_questions' => true, 'shuffle_options' => true,
        'show_results' => false, 'grade_scale' => 5.0, 'is_published' => false,
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class)->orderBy('position')->orderBy('id');
    }

    public function attempts()
    {
        return $this->hasMany(Attempt::class);
    }

    public function totalPoints(): float
    {
        return (float) $this->questions()->sum('points');
    }

    public function isOpen(): bool
    {
        $now = now();

        return $this->is_published
            && (! $this->opens_at || $now->gte($this->opens_at))
            && (! $this->closes_at || $now->lte($this->closes_at));
    }

    public function statusLabel(): string
    {
        if (! $this->is_published) {
            return 'Borrador';
        }
        if ($this->opens_at && now()->lt($this->opens_at)) {
            return 'Programada';
        }
        if ($this->closes_at && now()->gt($this->closes_at)) {
            return 'Cerrada';
        }

        return 'Abierta';
    }
}
