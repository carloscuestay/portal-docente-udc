<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AccessRequest;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Un estudiante con correo institucional que aún no está en ninguna lista
 * puede solicitar acceso; el docente lo aprueba o rechaza.
 */
class AccessRequestController extends Controller
{
    public function create(Request $request)
    {
        $profile = $request->session()->get('pending_google');
        if (! $profile) {
            return redirect()->route('login');
        }
        $subjects = Subject::where('is_active', true)->orderBy('program')->orderBy('name')->get();
        $existing = AccessRequest::with('subject')->where('email', $profile['email'])->latest()->get();

        return view('auth.access-request', compact('profile', 'subjects', 'existing'));
    }

    public function store(Request $request)
    {
        $profile = $request->session()->get('pending_google');
        abort_unless($profile, 403);
        $data = $request->validate([
            'subject_id' => ['required', Rule::exists('subjects', 'id')->where('is_active', true)],
            'student_code' => 'required|string|max:30',
        ]);

        AccessRequest::updateOrCreate(
            ['subject_id' => $data['subject_id'], 'email' => $profile['email']],
            ['name' => $profile['name'], 'student_code' => $data['student_code'], 'status' => 'pendiente']
        );

        return back()->with('ok', 'Solicitud enviada. Cuando el docente la apruebe podrá ingresar con su cuenta institucional.');
    }
}
