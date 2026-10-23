<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AccessRequest;
use App\Models\Enrollment;
use App\Models\Subject;
use App\Models\User;
use App\Services\RosterImporter;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function __construct(private RosterImporter $importer) {}

    public function index(Request $request, Subject $subject)
    {
        $this->ownSubject($subject);
        $search = trim((string) $request->query('q'));
        $like = '%'.mb_strtolower($search).'%';
        $students = $subject->students()
            ->when($search, fn ($q) => $q->where(fn ($w) => $w->whereRaw('lower(users.name) like ?', [$like])
                ->orWhereRaw('lower(users.email) like ?', [$like])->orWhere('users.student_code', 'like', $like)))
            ->get();
        $requests = $subject->accessRequests()->where('status', 'pendiente')->get();

        return view('teacher.enrollments.index', compact('subject', 'students', 'requests', 'search'));
    }

    public function import(Request $request, Subject $subject)
    {
        $this->ownSubject($subject);
        $request->validate([
            'file' => 'nullable|file|mimes:csv,txt|max:2048',
            'list' => 'nullable|string|max:200000',
        ]);
        $content = $request->hasFile('file') ? file_get_contents($request->file('file')->getRealPath()) : (string) $request->input('list');
        if (trim($content) === '') {
            return back()->withErrors(['list' => 'Adjunte un archivo CSV o pegue la lista.']);
        }

        $r = $this->importer->import($subject, $content);
        $msg = "Lista procesada: {$r['enrolled']} habilitados ({$r['created']} cuentas nuevas), {$r['already']} ya estaban en la lista.";

        return back()->with('ok', $msg)->with('import_errors', $r['errors']);
    }

    public function template(Subject $subject)
    {
        $this->ownSubject($subject);
        $domain = $this->importer->domain();
        $csv = "\xEF\xBB\xBFcodigo;nombre;correo\n0012345678;Ana María Pérez Gómez;aperezg@{$domain}\n0012345679;Luis Carlos Díaz;ldiazc@{$domain}\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="plantilla_estudiantes.csv"',
        ]);
    }

    public function toggle(Subject $subject, Enrollment $enrollment)
    {
        $this->ownSubject($subject);
        abort_unless($enrollment->subject_id === $subject->id, 404);
        $enrollment->update(['is_active' => ! $enrollment->is_active]);

        return back()->with('ok', $enrollment->is_active ? 'Estudiante habilitado.' : 'Estudiante deshabilitado para esta asignatura.');
    }

    public function destroy(Subject $subject, Enrollment $enrollment)
    {
        $this->ownSubject($subject);
        abort_unless($enrollment->subject_id === $subject->id, 404);
        $enrollment->delete();

        return back()->with('ok', 'Estudiante retirado de la lista.');
    }

    public function approve(AccessRequest $accessRequest)
    {
        $this->ownSubject($accessRequest->subject);
        $user = User::firstOrCreate(
            ['email' => $accessRequest->email],
            ['name' => $accessRequest->name, 'student_code' => $accessRequest->student_code, 'program' => $accessRequest->subject->program, 'role' => User::ESTUDIANTE]
        );
        abort_if($user->isTeacher(), 422, 'El correo pertenece a un docente.');
        Enrollment::updateOrCreate(['subject_id' => $accessRequest->subject_id, 'user_id' => $user->id], ['is_active' => true]);
        $accessRequest->update(['status' => 'aprobada']);

        return back()->with('ok', "Acceso aprobado para {$user->name}. Ya puede ingresar con Google.");
    }

    public function reject(AccessRequest $accessRequest)
    {
        $this->ownSubject($accessRequest->subject);
        $accessRequest->update(['status' => 'rechazada']);

        return back()->with('ok', 'Solicitud rechazada.');
    }
}
