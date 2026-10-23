@extends('layouts.app')
@section('title', 'Solicitar acceso')
@section('content')
<div class="login-box" style="max-width:560px">
    <div class="card">
        <h1>Aún no está en una lista</h1>
        <p class="muted">Ingresó como <strong>{{ $profile['name'] }}</strong> ({{ $profile['email'] }}), pero su correo no aparece
            en la lista de ninguna asignatura activa. Puede solicitar acceso y el docente lo revisará.</p>

        @if($existing->isNotEmpty())
            <h3>Sus solicitudes</h3>
            <ul class="list">
                @foreach($existing as $r)
                    <li><span>{{ $r->subject->label }}</span>
                        <span class="badge {{ $r->status === 'aprobada' ? 'badge-ok' : ($r->status === 'rechazada' ? 'badge-err' : 'badge-warn') }}">{{ ucfirst($r->status) }}</span></li>
                @endforeach
            </ul>
            @if($existing->contains('status', 'aprobada'))
                <p><a class="btn" href="{{ route('auth.google') }}">Ingresar nuevamente</a></p>
            @endif
        @endif

        <form method="POST" action="{{ route('access.store') }}">
            @csrf
            <label for="subject_id">Asignatura</label>
            <select id="subject_id" name="subject_id" required>
                <option value="">Seleccione…</option>
                @foreach($subjects->groupBy('program') as $program => $list)
                    <optgroup label="{{ $program }}">
                        @foreach($list as $s)<option value="{{ $s->id }}" @selected(old('subject_id') == $s->id)>{{ $s->label }}</option>@endforeach
                    </optgroup>
                @endforeach
            </select>
            <label for="student_code">Código estudiantil</label>
            <input id="student_code" type="text" name="student_code" value="{{ old('student_code') }}" required maxlength="30">
            <p><button class="btn" type="submit">Enviar solicitud</button>
                <a class="btn btn-sec" href="{{ route('login') }}">Volver</a></p>
        </form>
    </div>
</div>
@endsection
