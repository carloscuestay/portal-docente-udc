<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Support\PortalSettings;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function edit()
    {
        return view('teacher.settings', ['settings' => PortalSettings::all()]);
    }

    public function update(Request $request)
    {
        $open = $request->boolean('open_registration');
        $data = $request->validate([
            'institutional_domain' => [$open ? 'nullable' : 'required', 'string', 'max:120', 'regex:/^@?[a-z0-9.-]+\.[a-z]{2,}$/i'],
        ], [
            'institutional_domain.required' => 'Indique el dominio institucional o active el registro con cualquier correo.',
            'institutional_domain.regex' => 'Escriba solo el dominio, por ejemplo: unicartagena.edu.co',
        ]);
        PortalSettings::save($open, ltrim((string) ($data['institutional_domain'] ?? ''), '@'));

        return redirect()->route('teacher.settings.edit')->with('ok', 'Configuración guardada.');
    }
}
