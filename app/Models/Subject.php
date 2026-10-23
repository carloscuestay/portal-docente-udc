<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    public const PROGRAMS = ['Ingeniería de Sistemas', 'Administración Industrial'];

    protected $fillable = ['teacher_id', 'code', 'name', 'program', 'period', 'group', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    protected $attributes = ['is_active' => true];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function students()
    {
        return $this->belongsToMany(User::class, 'enrollments')
            ->withPivot(['id', 'is_active'])->withTimestamps()->orderBy('name');
    }

    public function materials()
    {
        return $this->hasMany(Material::class)->orderBy('position')->orderByDesc('created_at');
    }

    public function quizzes()
    {
        return $this->hasMany(Quiz::class)->orderByDesc('created_at');
    }

    public function accessRequests()
    {
        return $this->hasMany(AccessRequest::class);
    }

    public function getLabelAttribute(): string
    {
        return "{$this->code} · {$this->name}".($this->group ? " (G{$this->group})" : '')." — {$this->period}";
    }
}
