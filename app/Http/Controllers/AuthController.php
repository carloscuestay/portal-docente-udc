<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\GoogleOAuth;
use App\Services\RosterImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use RuntimeException;

class AuthController extends Controller
{
    public function __construct(private GoogleOAuth $google, private RosterImporter $roster) {}

    public function showLogin()
    {
        return view('auth.login', ['googleEnabled' => $this->google->configured()]);
    }

    /** Ingreso con contraseña: solo para cuentas docentes (respaldo si Google no está disponible). */
    public function login(Request $request)
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        $user = User::where('email', Str::lower($data['email']))->first();

        if (! $user || ! $user->isTeacher() || ! $user->password || ! Auth::attempt(['email' => $user->email, 'password' => $data['password']], $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Credenciales no válidas. Los estudiantes ingresan con su cuenta institucional de Google.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $user->update(['last_login_at' => now()]);

        return redirect()->intended(route('dashboard'));
    }

    public function redirectToGoogle(Request $request)
    {
        abort_unless($this->google->configured(), 404, 'El ingreso con Google no está configurado.');
        $state = Str::random(40);
        $request->session()->put('oauth_state', $state);

        return redirect()->away($this->google->authUrl($state));
    }

    public function handleGoogleCallback(Request $request)
    {
        $expected = $request->session()->pull('oauth_state');
        if (! $expected || ! hash_equals($expected, (string) $request->query('state'))) {
            return redirect()->route('login')->withErrors(['email' => 'La sesión de ingreso expiró. Intente de nuevo.']);
        }
        if ($request->filled('error') || ! $request->filled('code')) {
            return redirect()->route('login')->withErrors(['email' => 'Se canceló el ingreso con Google.']);
        }

        try {
            $profile = $this->google->user($request->query('code'));
        } catch (RuntimeException $e) {
            report($e);

            return redirect()->route('login')->withErrors(['email' => $e->getMessage()]);
        }

        $email = $profile['email'];
        $isTeacherEmail = in_array($email, config('portal.teacher_emails'), true);

        if (! $isTeacherEmail && ! $this->roster->isInstitutional($email)) {
            return redirect()->route('login')->withErrors([
                'email' => 'Debe ingresar con su correo institucional @'.$this->roster->domain().'.',
            ]);
        }

        $user = User::where('email', $email)->first();

        if ($isTeacherEmail) {
            $user ??= new User(['email' => $email]);
            $user->fill(['name' => $user->name ?: $profile['name'], 'role' => User::DOCENTE]);
        } elseif (! $user || ! $user->activeSubjects()->exists()) {
            // Estudiante no autorizado aún: puede solicitar acceso a una asignatura.
            $request->session()->put('pending_google', $profile);

            return redirect()->route('access.create');
        }

        if (! $user->is_active && ! $isTeacherEmail) {
            return redirect()->route('login')->withErrors(['email' => 'Su cuenta está deshabilitada. Comuníquese con el docente.']);
        }

        $user->fill([
            'google_id' => $profile['id'],
            'avatar_url' => $profile['avatar'],
            'last_login_at' => now(),
            'email_verified_at' => $user->email_verified_at ?? now(),
        ]);
        // Al ser la primera vez, se toma el nombre real de Google si la lista solo traía el usuario del correo.
        if ($user->name === Str::before($email, '@')) {
            $user->name = $profile['name'];
        }
        $user->save();

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
