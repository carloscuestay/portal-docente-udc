@extends('layouts.app')
@section('title', 'Ingresar')
@section('content')
<div class="login-box">
    <div class="card">
        <h1>Ingresar</h1>
        <p class="muted small">Estudiantes: use su correo institucional <strong>{{ '@'.config('portal.institutional_domain') }}</strong>.
            La primera vez ingrese con la <strong>clave inicial</strong> que le dio el docente
            @unless(config('portal.student_default_password'))(su <strong>código estudiantil</strong>)@endunless
            y el portal le pedirá crear su propia contraseña.</p>

        <form method="POST" action="{{ route('login.password') }}">
            @csrf
            <label for="email">Correo</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" placeholder="usuario{{ '@'.config('portal.institutional_domain') }}">
            <label for="password">Contraseña</label>
            <input id="password" type="password" name="password" required autocomplete="current-password">
            <label class="check"><input type="checkbox" name="remember" value="1"> Mantener la sesión</label>
            <button class="btn" type="submit" style="width:100%;justify-content:center">Ingresar</button>
        </form>

        @if($googleEnabled)
            <div class="divider">o</div>
            <a class="btn btn-google" href="{{ route('auth.google') }}">
                <svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34 6.1 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.4-.4-3.5z"/><path fill="#FF3D00" d="m6.3 14.7 6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34 6.1 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-8l-6.5 5C9.5 39.6 16.2 44 24 44z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.2-4.1 5.6l6.2 5.2C37 39.2 44 34 44 24c0-1.3-.1-2.4-.4-3.5z"/></svg>
                Continuar con Google institucional
            </a>
        @endif
        <p class="small muted" style="margin-top:16px">¿Olvidó su contraseña? Pida al docente que la restablezca; volverá a ingresar con la clave inicial.</p>
    </div>
</div>
@endsection
