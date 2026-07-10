<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $role = auth()->user()->role;
        $isAdmin = (is_string($role) && $role === 'admin') || ($role instanceof \App\Enums\UserRole && $role->value === 'admin');
        
        if (auth()->check() && $isAdmin) {
            return $next($request);
        }

        return redirect()->route('home')->with('error', 'No tienes permisos para acceder al panel de administración.');
    }
}
