<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const DOCENTE = 'docente';
    public const ESTUDIANTE = 'estudiante';

    protected $fillable = [
        'name', 'email', 'password', 'role', 'student_code', 'program',
        'google_id', 'avatar_url', 'is_active', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $attributes = ['role' => self::ESTUDIANTE, 'is_active' => true];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isTeacher(): bool
    {
        return $this->role === self::DOCENTE;
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'enrollments')
            ->withPivot('is_active')->withTimestamps();
    }

    /** Asignaturas activas en las que el estudiante está habilitado. */
    public function activeSubjects()
    {
        return $this->subjects()->wherePivot('is_active', true)->where('subjects.is_active', true);
    }

    public function taughtSubjects()
    {
        return $this->hasMany(Subject::class, 'teacher_id');
    }

    public function attempts()
    {
        return $this->hasMany(Attempt::class);
    }

    public function canAccessSubject(Subject $subject): bool
    {
        if ($this->isTeacher()) {
            return $subject->teacher_id === $this->id;
        }

        return $subject->is_active && $this->activeSubjects()->whereKey($subject->id)->exists();
    }
}
