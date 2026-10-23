<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MaterialController extends Controller
{
    private const MIMES = 'pdf,doc,docx,xls,xlsx,ppt,pptx,csv,txt,zip,rar,png,jpg,jpeg,gif,ipynb,py,sql,json,md,mp4,mp3';

    public function create(Subject $subject)
    {
        $this->ownSubject($subject);

        return view('teacher.materials.form', [
            'subject' => $subject,
            'material' => new Material(['type' => 'archivo', 'is_published' => true]),
            'units' => $subject->materials()->whereNotNull('unit')->distinct()->pluck('unit'),
        ]);
    }

    public function store(Request $request, Subject $subject)
    {
        $this->ownSubject($subject);
        $data = $this->validated($request, true);
        $material = new Material($data);
        $material->subject_id = $subject->id;
        $this->attachFile($request, $material);
        $material->save();

        return redirect()->route('teacher.subjects.show', $subject)->with('ok', 'Material publicado.');
    }

    public function edit(Material $material)
    {
        $subject = $this->ownSubject($material->subject);

        return view('teacher.materials.form', [
            'subject' => $subject, 'material' => $material,
            'units' => $subject->materials()->whereNotNull('unit')->distinct()->pluck('unit'),
        ]);
    }

    public function update(Request $request, Material $material)
    {
        $this->ownSubject($material->subject);
        $material->fill($this->validated($request, false, $material));
        $this->attachFile($request, $material);
        $material->save();

        return redirect()->route('teacher.subjects.show', $material->subject_id)->with('ok', 'Material actualizado.');
    }

    public function destroy(Material $material)
    {
        $this->ownSubject($material->subject);
        if ($material->file_path) {
            Storage::disk('local')->delete($material->file_path);
        }
        $material->delete();

        return back()->with('ok', 'Material eliminado.');
    }

    private function validated(Request $request, bool $creating, ?Material $material = null): array
    {
        $type = $request->input('type');
        $needsFile = $type === 'archivo' && ($creating || ! $material?->file_path);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'unit' => 'nullable|string|max:120',
            'description' => 'nullable|string|max:5000',
            'type' => ['required', Rule::in(array_keys(Material::TYPES))],
            'file' => [$needsFile ? 'required' : 'nullable', 'file', 'max:'.config('portal.max_upload_kb'), 'mimes:'.self::MIMES],
            'url' => ['nullable', 'required_if:type,enlace', 'url:http,https', 'max:1000'],
            'body' => 'nullable|required_if:type,texto|string|max:100000',
            'position' => 'nullable|integer|min:0|max:9999',
        ]);
        unset($data['file']);
        $data['is_published'] = $request->boolean('is_published');
        $data['position'] = (int) ($data['position'] ?? 0);

        return $data;
    }

    private function attachFile(Request $request, Material $material): void
    {
        if ($material->type !== 'archivo' || ! $request->hasFile('file')) {
            return;
        }
        if ($material->file_path) {
            Storage::disk('local')->delete($material->file_path);
        }
        $file = $request->file('file');
        $material->file_path = $file->store('materiales/'.$material->subject_id, 'local');
        $material->file_name = $file->getClientOriginalName();
        $material->file_size = $file->getSize();
    }
}
