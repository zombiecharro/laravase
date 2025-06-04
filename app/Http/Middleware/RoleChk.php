<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;

class RoleChk
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // Obtiene el usuario autenticado
        $user = $request->user();

        // Si no hay usuario autenticado o no es instancia de User, rechaza
        if (!($user instanceof User) || !in_array($user->role, $roles)) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        return $next($request);
    }
}
