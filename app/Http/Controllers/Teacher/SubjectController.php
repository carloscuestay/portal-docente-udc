<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
{
    public function index(Request $request)
    {
        return redirect()->route('dashboard');
    }

    public function create()
    {
        return view('teacher.subjects.form', ['subject' => new Subject(['is_active' => true, 'period' => $this->currentPeriod()])]);
    }

    public function store(Request $request)
    {
        $subject = $request->user()->taughtSubjects()->create($this->validated($request));

        return redirect()->route('teacher.subjects.show', $subject)->with('ok', 'Asignatura creada. Ahora cargue la lista de estudiantes.');
    }

    public function show(Subject $subject)
    {
        $this->ownSubject($subject);
        $subject->loadCount(['enrollments as active_students_count' => fn ($q) => $q->where('is_active', true)]);
        $materials = $subject->materials()->get()->groupBy(fn ($m) => $m->unit ?: 'General');
        $quizzes = $subject->quizzes()->withCount(['questions', 'attempts'])->get();
        $requests = $subject->accessRequests()->where('status', 'pendiente')->get();

        return view('teacher.subjects.show', compact('subject', 'materials', 'quizzes', 'requests'));
    }

    public function edit(Subject $subject)
    {
        return view('teacher.subjects.form', ['subject' => $this->ownSubject($subject)]);
    }

    public function update(Request $request, Subject $subject)
    {
        $this->ownSubject($subject)->update($this->validated($request, $subject));

        return redirect()->route('teacher.subjects.show', $subject)->with('ok', 'Asignatura actualizada.');
    }

    public function destroy(Request $request, Subject $subject)
    {
        $this->ownSubject($subject);
        $request->validate(['confirm' => ['required', Rule::in([$subject->code])]], ['confirm.in' => 'Escriba el código de la asignatura para confirmar.']);
        $subject->delete();

        return redirect()->route('dashboard')->with('ok', 'Asignatura eliminada.');
    }

    private function validated(Request $request, ?Subject $subject = null): array
    {
        $data = $request->validate([
            'code' => 'required|string|max:30',
            'name' => 'required|string|max:255',
            'program' => ['required', 'string', 'max:120'],
            'period' => ['required', 'regex:/^\d{4}-[12]$/'],
            'group' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:5000',
        ], ['period.regex' => 'El periodo debe tener el formato AAAA-1 o AAAA-2.']);
        $data['is_active'] = $request->boolean('is_active');

        $dup = Subject::where('code', $data['code'])->where('period', $data['period'])->where('group', $data['group'])
            ->when($subject, fn ($q) => $q->whereKeyNot($subject->id))->exists();
        if ($dup) {
            throw \Illuminate\Validation\ValidationException::withMessages(['code' => 'Ya existe esa asignatura para el mismo periodo y grupo.']);
        }

        return $data;
    }

    private function currentPeriod(): string
    {
        return now()->year.'-'.(now()->month <= 6 ? 1 : 2);
    }
}
