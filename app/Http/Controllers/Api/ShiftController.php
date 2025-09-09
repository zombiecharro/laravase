<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shift;

class ShiftController extends Controller
{
    // Listar todos los shifts del usuario autenticado
    public function index(Request $request)
    {
        $user = $request->user();
        $shifts = Shift::where('user_id', $user->id)->get();
        return response()->json($shifts);
    }

    // Crear un nuevo shift
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'name' => 'nullable|string|max:255',
            'global' => 'boolean',
            'reverse' => 'boolean',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);
        $validated['user_id'] = $validated['user_id'] ?? $request->user()->id;
        $shift = Shift::create($validated);
        return response()->json($shift, 201);
    }

    // Mostrar un shift específico
    public function show($id, Request $request)
    {
        $shift = Shift::where('user_id', $request->user()->id)->findOrFail($id);
        return response()->json($shift);
    }

    // Actualizar un shift (PATCH)
    public function update(Request $request, $id)
    {
        $shift = Shift::where('user_id', $request->user()->id)->findOrFail($id);
        $validated = $request->validate([
            'user_id' => 'sometimes|nullable|exists:users,id',
            'name' => 'nullable|string|max:255',
            'global' => 'boolean',
            'reverse' => 'boolean',
            'start_time' => 'sometimes|required|date_format:H:i',
            'end_time' => 'sometimes|required|date_format:H:i|after:start_time',
        ]);
        $shift->update($validated);
        return response()->json($shift);
    }

    // Eliminar un shift
    public function destroy($id, Request $request)
    {
        $shift = Shift::where('user_id', $request->user()->id)->findOrFail($id);
        $shift->delete();
        return response()->json(['message' => 'Shift eliminado correctamente']);
    }
}
