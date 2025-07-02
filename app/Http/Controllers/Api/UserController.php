<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
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
            
            // Campos del perfil (opcionales)
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:1000',
            'birth_date' => 'nullable|date',
            
            // Campos de dirección (opcionales)
            'street' => 'nullable|string|max:255',
            'street_number' => 'nullable|string|max:20',
            'apartment' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:100',
            'additional_info' => 'nullable|string|max:500',
        ]);

        $user = DB::transaction(function () use ($validated) {
            // Crear el usuario
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
            ]);

            // Crear perfil (siempre se crea, aunque esté vacío)
            $user->profile()->create([
                'first_name' => $validated['first_name'] ?? null,
                'last_name' => $validated['last_name'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'bio' => $validated['bio'] ?? null,
                'birth_date' => $validated['birth_date'] ?? null,
                'preferences' => [], // JSON vacío por defecto
            ]);

            // Crear dirección por defecto (solo si se proporcionan datos básicos)
            if (!empty($validated['street']) || !empty($validated['city'])) {
                $user->addresses()->create([
                    'street' => $validated['street'] ?? '',
                    'street_number' => $validated['street_number'] ?? null,
                    'apartment' => $validated['apartment'] ?? null,
                    'city' => $validated['city'] ?? '',
                    'state' => $validated['state'] ?? '',
                    'postal_code' => $validated['postal_code'] ?? '',
                    'country' => $validated['country'] ?? 'México',
                    'additional_info' => $validated['additional_info'] ?? null,
                    'is_default' => true, // Primera dirección es por defecto
                ]);
            }

            // Cargar las relaciones para el retorno
            $user->load(['profile', 'addresses']);
            
            return $user;
        });

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

        // Cargar relaciones para el retorno
        $user->load(['profile', 'addresses']);

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
