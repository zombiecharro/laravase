<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AddressController extends Controller
{

    /**
     * Actualizar dirección del usuario autenticado
     */
    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'street' => 'nullable|string|max:255',
            'street_number' => 'nullable|string|max:50',
            'apartment' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'additional_info' => 'nullable|string|max:500',
            'is_default' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = auth()->user();
        $data = $validator->validated();

        // Si no tiene dirección, crearla
        if ($user->addresses()->count() === 0) {
            $data['is_default'] = true;
            $address = $user->addresses()->create($data);
            
            return response()->json([
                'message' => 'Dirección creada exitosamente',
                'address' => $address
            ], 201);
        }

        // Actualizar la primera (y única) dirección
        $address = $user->addresses()->first();
        $address->update($data);

        return response()->json([
            'message' => 'Dirección actualizada exitosamente',
            'address' => $address->fresh()
        ]);
    }
}
