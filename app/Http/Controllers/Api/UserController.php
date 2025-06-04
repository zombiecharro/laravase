<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\UserResource;

class UserController extends Controller
{
    // Listar todos los usuarios
    public function index()
    {
        return UserResource::collection(User::all());
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
}
