<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureTeacher
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->user()?->isTeacher(), 403, 'Sección exclusiva del docente.');

        return $next($request);
    }
}
