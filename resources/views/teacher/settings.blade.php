@extends('layouts.app')
@section('title', 'Configuración')
@section('content')
<div class="breadcrumb"><a href="{{ route('dashboard') }}">Inicio</a> / Configuración</div>
<h1>Configuración del portal</h1>
<form class="card" method="POST" action="{{ route('teacher.settings.update') }}">
    @csrf
    @method('PUT')
    <h3>Registro de estudiantes</h3>
    <label class="check"><input type="checkbox" name="open_registration" value="1" id="open" @checked(old('open_registration', $settings['open_registration']))> Permitir estudiantes con <strong>cualquier correo</strong> (Gmail, Outlook, otras instituciones…)</label>
    <p class="muted small">Si no lo marca, solo se aceptan en las listas y en el ingreso los correos del dominio institucional.</p>
    <label for="institutional_domain">Dominio institucional</label>
    <input id="institutional_domain" type="text" name="institutional_domain" value="{{ old('institutional_domain', $settings['institutional_domain']) }}" placeholder="unicartagena.edu.co" maxlength="120">
    <p class="muted small">Se usa cuando el registro abierto está desactivado. Escriba solo el dominio, sin la arroba.</p>
    <div class="actions" style="margin-top:16px"><button class="btn" type="submit">Guardar</button></div>
</form>
<p class="muted small">En cualquier caso, solo ingresan los estudiantes que usted agregue a la lista de cada asignatura o cuya solicitud de acceso apruebe.</p>
@endsection
