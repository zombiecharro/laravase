<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ImageController extends Controller
{
    public function upload(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|file|image|mimes:jpeg,png,jpg,gif|max:2048',
            'type' => 'required|in:avatar,product,gallery',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $file = $request->file('image');
        $type = $request->input('type');
        
        // Organizar por tipo de imagen
        $folder = match($type) {
            'avatar' => 'avatars',
            'product' => 'products',
            'gallery' => 'gallery',
            default => 'images'
        };

        // Generar nombre único
        $fileName = time() . '_' . Str::random(10) . '.' . $file->extension();
        
        // Guardar archivo
        $filePath = $file->storeAs($folder, $fileName, 'public');

        return response()->json([
            'message' => 'Imagen subida exitosamente',
            'url' => '/storage/' . $filePath,
            'filename' => $fileName,
            'type' => $type
        ], 201);
    }
}
