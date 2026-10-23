@extends('layouts.app')
@section('title', $material->exists ? 'Editar material' : 'Agregar material')
@section('content')
<div class="breadcrumb"><a href="{{ route('dashboard') }}">Inicio</a> / <a href="{{ route('teacher.subjects.show', $subject) }}">{{ $subject->code }}</a> / Material</div>
<h1>{{ $material->exists ? 'Editar material' : 'Agregar material' }}</h1>
<form class="card" method="POST" enctype="multipart/form-data"
      action="{{ $material->exists ? route('teacher.materials.update', $material) : route('teacher.materials.store', $subject) }}">
    @csrf
    @if($material->exists) @method('PUT') @endif
    <label for="title">Título</label>
    <input id="title" type="text" name="title" value="{{ old('title', $material->title) }}" required maxlength="255">
    <div class="row">
        <div>
            <label for="unit">Unidad / semana <span class="hint">(agrupa el material)</span></label>
            <input id="unit" type="text" name="unit" list="units" value="{{ old('unit', $material->unit) }}" maxlength="120" placeholder="Unidad 1 · Semana 3">
            <datalist id="units">@foreach($units as $u)<option value="{{ $u }}">@endforeach</datalist>
        </div>
        <div>
            <label for="position">Orden</label>
            <input id="position" type="number" name="position" min="0" value="{{ old('position', $material->position) }}">
        </div>
        <div>
            <label for="type">Tipo</label>
            <select id="type" name="type" data-toggle-type>
                @foreach(\App\Models\Material::TYPES as $k => $v)<option value="{{ $k }}" @selected(old('type', $material->type) === $k)>{{ $v }}</option>@endforeach
            </select>
        </div>
    </div>
    <label for="description">Descripción breve</label>
    <textarea id="description" name="description" style="min-height:70px">{{ old('description', $material->description) }}</textarea>

    <div data-type="archivo">
        <label for="file">Archivo <span class="hint">(máx. {{ round(config('portal.max_upload_kb') / 1024) }} MB · PDF, Office, imágenes, ZIP, notebooks, etc.)</span></label>
        @if($material->file_name)<p class="small">Actual: {{ $material->file_name }} ({{ $material->humanSize() }}). Suba otro para reemplazarlo.</p>@endif
        <input id="file" type="file" name="file">
    </div>
    <div data-type="enlace">
        <label for="url">Enlace (URL)</label>
        <input id="url" type="url" name="url" value="{{ old('url', $material->url) }}" placeholder="https://">
    </div>
    <div data-type="texto">
        <label for="body">Contenido</label>
        <textarea id="body" name="body" style="min-height:220px">{{ old('body', $material->body) }}</textarea>
    </div>

    <label class="check"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $material->is_published))> Visible para los estudiantes</label>
    <div class="actions" style="margin-top:16px">
        <button class="btn" type="submit">Guardar</button>
        <a class="btn btn-sec" href="{{ route('teacher.subjects.show', $subject) }}">Cancelar</a>
    </div>
</form>
@push('scripts')
<script>
(function () {
  var sel = document.querySelector('[data-toggle-type]');
  function sync() { document.querySelectorAll('[data-type]').forEach(function (el) { el.hidden = el.dataset.type !== sel.value; }); }
  sel.addEventListener('change', sync); sync();
})();
</script>
@endpush
@endsection
