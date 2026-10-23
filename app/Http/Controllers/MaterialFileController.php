<?php

namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Entrega los archivos de material solo a usuarios autorizados (disco privado, sin enlace público). */
class MaterialFileController extends Controller
{
    public function __invoke(Request $request, Material $material)
    {
        $user = $request->user();
        abort_unless($material->type === 'archivo' && $material->file_path, 404);
        abort_unless($user->canAccessSubject($material->subject), 403);
        abort_if(! $user->isTeacher() && ! $material->is_published, 404);
        abort_unless(Storage::disk('local')->exists($material->file_path), 404, 'Archivo no encontrado.');

        $inline = $request->boolean('ver') && str_ends_with(strtolower($material->file_name), '.pdf');

        return $inline
            ? Storage::disk('local')->response($material->file_path, $material->file_name)
            : Storage::disk('local')->download($material->file_path, $material->file_name);
    }
}
