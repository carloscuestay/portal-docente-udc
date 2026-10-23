<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureStudent
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        abort_if(! $user || $user->isTeacher(), 403, 'Sección exclusiva de estudiantes.');

        return $next($request);
    }
}
