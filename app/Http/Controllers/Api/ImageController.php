<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\Image;

class ImageController extends Controller
{
    public function upload(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|file|image|mimes:jpeg,png,jpg,gif|max:2048',
            'type' => 'required|in:avatar,product,gallery',
            'imageable_type' => 'nullable|string',
            'imageable_id' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $file = $request->file('image');
        $type = $request->input('type');
        $iType = $request->input('imageable_type', null);
        $iId = $request->input('imageable_id', null);

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
        $url = '/storage/' . $filePath;

        // Guardar en base de datos
        $imageData = [
            'filename' => $fileName,
            'url' => $url,
            'type' => $type,
        ];

        // Solo agregar campos imageable si están presentes
        if ($iId !== null && $iType !== null) {
            $imageData['imageable_id'] = $iId;
            $imageData['imageable_type'] = $iType;
        }

        $image = Image::create($imageData);

        return response()->json([
            'message' => 'Imagen subida exitosamente',
            'image_id' => $image->id,
            'url' => $url,
            'filename' => $fileName,
            'type' => $type
        ], 201);
    }
}
