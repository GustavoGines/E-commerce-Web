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
        // Guard 1: el usuario debe estar autenticado.
        // Esto DEBE evaluarse antes de acceder a auth()->user() para evitar
        // un TypeError si la sesión está corrupta o el token expiró.
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        // Guard 2: el usuario autenticado debe tener rol de administrador.
        // Usa el helper isAdmin() del modelo User para centralizar la lógica de rol.
        if (auth()->user()->isAdmin()) {
            return $next($request);
        }

        return redirect()->route('home')->with('error', 'No tienes permisos para acceder al panel de administración.');
    }
}
