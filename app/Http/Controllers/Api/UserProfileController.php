<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class UserProfileController extends Controller
{
    /**
     * Crear perfil del usuario autenticado
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:1000',
            'profile_picture' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'preferences' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = auth()->user();

        // Verificar si ya tiene perfil
        if ($user->profile) {
            return response()->json([
                'message' => 'El usuario ya tiene un perfil. Use PUT para actualizar.',
                'profile' => $user->profile
            ], 409);
        }

        $profile = $user->profile()->create($validator->validated());

        return response()->json([
            'message' => 'Perfil creado exitosamente',
            'profile' => $profile
        ], 201);
    }

    /**
     * Actualizar perfil del usuario autenticado
     */
    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:1000',
            'profile_picture' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'preferences' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = auth()->user();

        // Si no tiene perfil, crearlo
        if (!$user->profile) {
            $profile = $user->profile()->create($validator->validated());
            return response()->json([
                'message' => 'Perfil creado exitosamente',
                'profile' => $profile
            ], 201);
        }

        // Actualizar perfil existente
        $user->profile->update($validator->validated());

        return response()->json([
            'message' => 'Perfil actualizado exitosamente',
            'profile' => $user->profile->fresh()
        ]);
    }
}
