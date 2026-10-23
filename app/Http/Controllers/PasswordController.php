<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    public function edit(Request $request)
    {
        return view('auth.change-password', ['forced' => (bool) $request->user()->must_change_password]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $rules = ['password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()]];
        if ($user->password && ! $user->must_change_password) {
            $rules['current_password'] = ['required', 'current_password'];
        }
        $request->validate($rules, [
            'current_password.current_password' => 'La contraseña actual no es correcta.',
            'password.confirmed' => 'La confirmación no coincide.',
        ]);

        $new = $request->input('password');
        $initial = $user->isTeacher() ? null : $user->initialPassword();
        if (($initial && hash_equals($initial, $new)) || ($user->student_code && $new === $user->student_code)) {
            return back()->withErrors(['password' => 'La nueva contraseña no puede ser la clave inicial ni su código estudiantil.']);
        }
        if ($user->password && Hash::check($new, $user->password)) {
            return back()->withErrors(['password' => 'La nueva contraseña debe ser diferente a la actual.']);
        }

        $user->forceFill(['password' => $new, 'must_change_password' => false])->save();
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('ok', 'Contraseña actualizada.');
    }
}
