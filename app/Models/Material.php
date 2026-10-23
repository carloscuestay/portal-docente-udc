<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    public const TYPES = ['archivo' => 'Archivo', 'enlace' => 'Enlace', 'texto' => 'Texto / nota'];

    protected $fillable = [
        'subject_id', 'title', 'unit', 'description', 'type', 'file_path',
        'file_name', 'file_size', 'url', 'body', 'is_published', 'position',
    ];

    protected $casts = ['is_published' => 'boolean'];

    protected $attributes = ['is_published' => true, 'position' => 0];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function humanSize(): string
    {
        $b = (int) $this->file_size;
        foreach (['B', 'KB', 'MB', 'GB'] as $u) {
            if ($b < 1024) {
                return round($b, 1)." {$u}";
            }
            $b /= 1024;
        }

        return round($b, 1).' TB';
    }
}
