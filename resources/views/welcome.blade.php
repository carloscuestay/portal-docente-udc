@extends('layouts.app')
@section('title', 'Bienvenido')
@section('content')
<section class="hero">
    <h1>{{ config('app.name') }}</h1>
    <p style="max-width:640px;font-size:1.1rem;margin:8px 0 20px">
        Espacio de apoyo a la docencia para los programas de Ingeniería de Sistemas y Administración Industrial
        de la {{ config('portal.institution') }}: material de clase y evaluaciones en línea tipo Saber Pro.
    </p>
    <a class="btn btn-sec" href="{{ route('login') }}">Ingresar con correo institucional</a>
</section>
<div class="grid">
    <div class="card"><h3>Material por asignatura</h3><p class="muted">Presentaciones, guías, lecturas, enlaces y notas organizadas por unidad o semana.</p></div>
    <div class="card"><h3>Evaluaciones tipo Saber</h3><p class="muted">Selección múltiple, apareamiento, completar frases y respuesta libre con límite de caracteres.</p></div>
    <div class="card"><h3>Acceso controlado</h3><p class="muted">Solo ingresan los estudiantes autorizados por el docente con su correo {{ '@'.config('portal.institutional_domain') }}.</p></div>
</div>
@endsection
