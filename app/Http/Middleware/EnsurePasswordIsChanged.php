<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->debe_cambiar_contrasena
            && ! $request->routeIs('password.change', 'password.change.update')) {
            return redirect()->route('password.change');
        }

        return $next($request);
    }
}