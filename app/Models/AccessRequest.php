<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessRequest extends Model
{
    protected $fillable = ['subject_id', 'email', 'name', 'student_code', 'status'];

    protected $attributes = ['status' => 'pendiente'];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
