<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Appointment;

class AppointmentController extends Controller
{
    // Listar todas las citas del usuario autenticado (como cliente o lector)
    public function index(Request $request)
    {
        $user = $request->user();
        $status = $request->query('status');
        if ($user->role === 'admin') {
            $query = Appointment::query();
        } elseif ($user->role === 'staff') {
            $query = Appointment::where('reader_id', $user->id);
        } else {
            $query = Appointment::where('client_id', $user->id);
        }
        if ($status) {
            $query->where('status', $status);
        }
        $appointments = $query->get();
        return response()->json($appointments);
    }

    // Crear una nueva cita
    public function store(Request $request)
    {
        $validated = $request->validate([
            'reader_id' => 'required|exists:users,id',
            'client_id' => 'required|exists:users,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'status' => 'nullable|in:scheduled,completed,canceled',
            'notes' => 'nullable|string',
            'is_exception' => 'boolean',
        ]);
        $appointment = Appointment::create($validated);
        return response()->json($appointment, 201);
    }

    // Mostrar una cita específica
    public function show($id, Request $request)
    {
        $user = $request->user();
        if ($user->role === 'admin') {
            $appointment = Appointment::findOrFail($id);
        } elseif ($user->role === 'staff') {
            $appointment = Appointment::where('id', $id)
                ->where('reader_id', $user->id)
                ->firstOrFail();
        } else {
            $appointment = Appointment::where('id', $id)
                ->where('client_id', $user->id)
                ->firstOrFail();
        }
        return response()->json($appointment);
    }

    // Actualizar el status de una cita (PATCH)
    public function update(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role === 'admin') {
            $appointment = Appointment::findOrFail($id);
        } elseif ($user->role === 'staff') {
            $appointment = Appointment::where('id', $id)
                ->where('reader_id', $user->id)
                ->firstOrFail();
        } else {
            $appointment = Appointment::where('id', $id)
                ->where('client_id', $user->id)
                ->firstOrFail();
        }
        $validated = $request->validate([
            'status' => 'required|in:scheduled,completed,canceled',
        ]);
        $appointment->update($validated);
        return response()->json($appointment);
    }
}
