<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class APIKEY
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // if($request->api_password !== env('API_KEY','xdUK4rdNAlE7aXWZOupXl9feNjiNOzP0SoE05dDYQ5o2nt5yuz3qhQtFDIyWLGDj')){
        //     return response(['message'=>'Unauthenticated.']);
        // }
        // return $next($request);

        $apiPassword = $request->header('x-api-key') ?: $request->input('api_password');

        if ($apiPassword !== env('API_KEY')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
