<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\UserResource;
use Laravel\Sanctum\PersonalAccessToken;

class UserController extends Controller
{
    // Listar todos los usuarios
    public function index()
    {
        $users = User::with(['profile', 'addresses'])->get();
        return UserResource::collection($users);
    }

    // Crear un nuevo usuario
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,staff,user',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        return new UserResource($user);
    }

    // Mostrar un usuario específico
    public function show(User $user)
    {
        $user->load(['profile', 'addresses']);
        return new UserResource($user);
    }

    // Actualizar un usuario
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|required|string|min:6',
            'role' => 'sometimes|required|in:admin,staff,user',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return new UserResource($user);
    }

    // Eliminar un usuario
    public function destroy(User $user)
    {
        $user->delete();
        return response()->noContent();
    }

    public function verify(Request $request)
    {
        $tokenEnviado = $request->bearerToken();
        if (!$tokenEnviado || !str_contains($tokenEnviado, '|')) {
            return response()->json(['message' => 'Token no proporcionado o formato inválido.'], 401);
        }

        [$tokenId, $tokenString] = explode('|', $tokenEnviado, 2);

        $token = PersonalAccessToken::find($tokenId);

        if (!$token) {
            return response()->json(['message' => 'Token no encontrado.'], 401);
        }
        if (!hash_equals($token->token, hash('sha256', $tokenString))) {
            return response()->json(['message' => 'Token inválido.'], 401);
        }
        if (isset($token->expires_at) && $token->expires_at < now()) {
            return response()->json(['message' => 'Token expirado.'], 401);
        }

        $user = $token->tokenable;

        // Cargar relaciones de profile y addresses
        $user->load(['profile', 'addresses']);

        return new UserResource($user);
    }
}
