<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\models\RefreshToken;

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
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $user = User::create($validated);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
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

        // Devuelve access_token en JSON y refresh_token como cookie HttpOnly
        return response()->json([
            'user' => $user,
            'access_token' => $accessToken,
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
