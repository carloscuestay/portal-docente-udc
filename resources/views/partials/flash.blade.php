@if(session('ok'))
    <div class="alert alert-ok" role="status">{{ session('ok') }}</div>
@endif
@if(session('import_errors'))
    <div class="alert alert-warn">
        <strong>Líneas no procesadas ({{ count(session('import_errors')) }}):</strong>
        <ul>@foreach(array_slice(session('import_errors'), 0, 30) as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif
@if($errors->any())
    <div class="alert alert-error" role="alert">
        <strong>Revise la información:</strong>
        <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif
