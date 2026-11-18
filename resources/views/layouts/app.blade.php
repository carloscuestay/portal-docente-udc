<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Inicio') · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=2">
</head>
<body>
<header class="topbar">
    <div class="container">
        @if(config('portal.logo') || config('portal.logo_secondary'))
        <div class="brand-logos">
            @if(config('portal.logo'))<a href="{{ config('portal.logo_url') ?: (auth()->check() ? route('dashboard') : route('home')) }}" title="{{ config('portal.institution') }}"><img src="{{ \Illuminate\Support\Str::startsWith(config('portal.logo'), 'http') ? config('portal.logo') : asset(config('portal.logo')) }}" alt="{{ config('portal.institution') }}"></a>@endif
            @if(config('portal.logo_secondary'))<a href="{{ auth()->check() ? route('dashboard') : route('home') }}" title="{{ config('app.name') }}"><img src="{{ \Illuminate\Support\Str::startsWith(config('portal.logo_secondary'), 'http') ? config('portal.logo_secondary') : asset(config('portal.logo_secondary')) }}" alt="{{ config('app.name') }}"></a>@endif
        </div>
        @else
        <a class="brand" href="{{ auth()->check() ? route('dashboard') : route('home') }}">
            {{ config('app.name') }}
            <small>{{ config('portal.institution') }}</small>
        </a>
        @endif
        <nav>
            @auth
                <a href="{{ route('dashboard') }}">Inicio</a>
                @if(auth()->user()->isTeacher())
                    <a href="{{ route('teacher.subjects.create') }}">+ Asignatura</a>
                    <a href="{{ route('teacher.settings.edit') }}">Configuración</a>
                @endif
                <span class="small" style="padding:0 8px">
                    @if(auth()->user()->avatar_url)<img class="avatar" src="{{ auth()->user()->avatar_url }}" alt="" referrerpolicy="no-referrer">@endif
                    {{ \Illuminate\Support\Str::words(auth()->user()->name, 2, '') }}
                </span>
                <a href="{{ route('password.edit') }}">Clave</a>
                <form method="POST" action="{{ route('logout') }}" class="inline">@csrf<button type="submit">Salir</button></form>
            @else
                <a href="{{ route('login') }}">Ingresar</a>
            @endauth
        </nav>
    </div>
</header>
<main>
    <div class="container">
        @include('partials.flash')
        @yield('content')
    </div>
</main>
<footer>
    {{ config('app.name') }} · {{ config('portal.institution') }} · {{ now()->year }}
</footer>
@stack('scripts')
</body>
</html>
