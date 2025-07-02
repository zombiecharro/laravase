<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\models\RefreshToken;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    // Registro de usuario
    public function signup(Request $request)
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

        $user = DB::transaction(function () use ($validated, $request) {
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

            // Access token
            $plainTextToken = $user->createToken('auth_token')->plainTextToken;

            // Separa el ID del token y el valor
            [$tokenId, $tokenString] = explode('|', $plainTextToken, 2);

            // Busca el registro en la base de datos y asigna la expiración
            $personalToken = \Laravel\Sanctum\PersonalAccessToken::find($tokenId);
            $personalToken->expires_at = now()->addMinutes(240);
            $personalToken->save();

            // Refresh token
            $refreshToken = bin2hex(random_bytes(40));
            $expireDate = now()->addDays(7);

            // Guarda el refresh token en la base de datos
            RefreshToken::create([
                'user_id' => $user->id,
                'refresh_token' => hash('sha256', $refreshToken),
                'expire_date' => $expireDate,
                'api_address' => $request->ip(),
            ]);

            // Cargar las relaciones para el retorno
            $user->load(['profile', 'addresses']);

            return [
                'user' => $user,
                'plainTextToken' => $plainTextToken,
                'refreshToken' => $refreshToken,
                'personalToken' => $personalToken
            ];
        });

        // Devuelve access_token en JSON y refresh_token como cookie HttpOnly
        return response()->json([
            'user' => $user['user'],
            'token' => $user['plainTextToken'],
            'expires_at' => $user['personalToken']->expires_at,
        ], 201)->cookie(
            'refresh_token',
            $user['refreshToken'],
            60 * 24 * 7, // 7 días en minutos
            null,
            null,
            false, // Secure (para localhost)
            true,  // HttpOnly
            false,
            'Strict'
        );
    }

    // Login de usuario
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales no son válidas.'],
            ]);
        }

        // Access token
        $accessToken = $user->createToken('auth_token')->plainTextToken;

        // Separa el ID del token y el valor
        [$tokenId, $tokenString] = explode('|', $accessToken, 2);

        // Busca el registro en la base de datos y asigna la expiración
        $personalToken = \Laravel\Sanctum\PersonalAccessToken::find($tokenId);
        $personalToken->expires_at = now()->addMinutes(240); // 4 horas
        $personalToken->save();

        // Refresh token (puedes usar Str::random o similar y guardarlo en la DB)
        $refreshToken = bin2hex(random_bytes(40));
        $expireDate = now()->addDays(7);

        // Guarda el refresh token en la base de datos
        RefreshToken::create([
            'user_id' => $user->id,
            'refresh_token' => hash('sha256', $refreshToken),
            'expire_date' => $expireDate,
            'api_address' => $request->ip(),
        ]);

        // Cargar relaciones para el retorno
        $user->load(['profile', 'addresses']);

        // Devuelve access_token en JSON y refresh_token como cookie HttpOnly
        return response()->json([
            'user' => $user,
            'token' => $accessToken,
        ])->cookie(
            'refresh_token',
            $refreshToken,
            60 * 24 * 7, // 7 días en minutos
            null,
            null,
            false, // Secure (para localhost)
            true, // HttpOnly
            false,
            'Strict'
        );
    }

    // Refresh token (revoca el actual y crea uno nuevo)
    public function refresh(Request $request)
    {
        // Obtén el refresh_token de la cookie
        $refreshToken = $request->cookie('refresh_token');
        if (!$refreshToken) {
            return response()->json(['message' => 'Refresh token no proporcionado.'], 401);
        }

        // Busca el token en la base de datos (hasheado)
        $hashedToken = hash('sha256', $refreshToken);
        $refreshTokenRecord = RefreshToken::where('refresh_token', $hashedToken)
            ->where('expire_date', '>', now())
            ->first();

        if (!$refreshTokenRecord) {
            return response()->json(['message' => 'Refresh token inválido o expirado.'], 401);
        }

        // Obtén el usuario asociado
        $user = $refreshTokenRecord->user;

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        // Opcional: Revoca tokens previos si lo deseas
        $user->tokens()->delete();

        // Crea un nuevo access token
        $accessToken = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $accessToken,
        ]);
    }

    // Logout de usuario
    public function logout(Request $request)
    {
        // Revoca el access token actual
        $request->user()?->currentAccessToken()?->delete();

        // Elimina el refresh_token de la base de datos si existe en la cookie
        $refreshToken = $request->cookie('refresh_token');
        if ($refreshToken) {
            $hashedToken = hash('sha256', $refreshToken);
            RefreshToken::where('refresh_token', $hashedToken)->delete();
        }

        // Borra la cookie del refresh_token
        return response()->json(['message' => 'Sesión cerrada correctamente.'])
            ->withoutCookie('refresh_token');
    }
}
