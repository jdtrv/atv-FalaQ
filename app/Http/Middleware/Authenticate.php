<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class Authenticate
{

    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Não autorizado.'], 401);
            }

            return redirect()->route('login.create')->with('error', 'Você precisa estar logado para acessar esta página.');
        }

        return $next($request);
    }
}