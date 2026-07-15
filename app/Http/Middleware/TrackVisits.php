<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\PageVisit;

class TrackVisits
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && !$request->ajax() && !$request->header('X-Livewire')) {
            $today = now()->format('Y-m-d');
            
            if ($request->session()->get('last_visit_date') !== $today) {
                try {
                    // Evitar que bots o peticiones sin cookies generen miles de visitas
                    // comprobando si esa IP ya registró una visita hoy.
                    $alreadyVisited = PageVisit::where('ip_address', $request->ip())
                                               ->whereDate('created_at', now()->toDateString())
                                               ->exists();
                                               
                    if (!$alreadyVisited) {
                        PageVisit::create([
                            'ip_address' => $request->ip(),
                            'url' => $request->fullUrl(),
                            'user_agent' => $request->userAgent(),
                        ]);
                    }
                    
                    $request->session()->put('last_visit_date', $today);
                } catch (\Exception $e) {
                    // Ignore errors
                }
            }
        }
        
        return $next($request);
    }
}
