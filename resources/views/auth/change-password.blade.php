@extends('layouts.app')
@section('title', 'Cambiar contraseña')
@section('content')
<div class="login-box">
    <div class="card">
        <h1>{{ $forced ? 'Cree su contraseña' : 'Cambiar contraseña' }}</h1>
        @if($forced)
            <p class="muted">Por seguridad debe reemplazar la clave inicial por una contraseña personal antes de continuar.</p>
        @endif
        <form method="POST" action="{{ route('password.update') }}">
            @csrf @method('PUT')
            @if(! $forced && auth()->user()->password)
                <label for="current_password">Contraseña actual</label>
                <input id="current_password" type="password" name="current_password" required autocomplete="current-password">
            @endif
            <label for="password">Nueva contraseña <span class="hint">(mínimo 8 caracteres, con letras y números)</span></label>
            <input id="password" type="password" name="password" required autocomplete="new-password" minlength="8">
            <label for="password_confirmation">Confirmar nueva contraseña</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" minlength="8">
            <p><button class="btn" type="submit">Guardar contraseña</button></p>
        </form>
    </div>
</div>
@endsection
