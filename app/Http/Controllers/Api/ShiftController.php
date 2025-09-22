<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shift;
use App\Models\Appointment;
use App\Services\AvailabilityService;

class ShiftController extends Controller
{
    /**
     * Devuelve los slots disponibles calculados por AvailabilityService.
     */
    public function getAvailableSlots(Request $request)
    {
        $service = new AvailabilityService();

        // Obtener usuario (puedes ajustar según tu lógica de autenticación)
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Usuario no autenticado'], 401);
        }

        // Obtener todas las citas del usuario (puedes ajustar el filtro si es necesario)
        $appointments = Appointment::where('client_id', $user->id)->get()->toArray();

        // Obtener año y mes de la request (query params)
        $year = $request->query('year');
        $month = $request->query('month');

        // Calcular los slots disponibles para ese mes
        $slots = $service->getAvailableSlots($user->id, $appointments, $year, $month);

        return response()->json(['slots' => $slots]);
    }
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
