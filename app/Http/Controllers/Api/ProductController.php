<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $products = Product::select('id', 'name', 'sku', 'price', 'stock', 'image_url', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'message' => 'Productos obtenidos exitosamente',
            'products' => $products
        ]);
    }

    /**
     * Store a newly created resource in storage. 
     */
    public function store(Request $request): JsonResponse{
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:255|unique:products,sku',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'category_id' => 'sometimes|integer|exists:categories,id',
            'image_url' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        
        // Asignar categoría por defecto si no se proporciona
        if (!isset($data['category_id'])) {
            $data['category_id'] = 1;
        }

        $product = Product::create($data);

        return response()->json([
            'message' => 'Producto creado exitosamente',
            'product' => $product
        ], 201);
    }
    /**
     * Display the specified resource.
     */
    public function show(Product $product): JsonResponse
    {
        // Cargar imágenes adicionales si existen
        $product->load('images');
        
        return response()->json([
            'message' => 'Producto obtenido exitosamente',
            'product' => $product
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'sku' => 'nullable|string|max:255|unique:products,sku,' . $product->id,
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'category_id' => 'nullable|integer|exists:categories,id',
            'image_url' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $product->update($validator->validated());

        return response()->json([
            'message' => 'Producto actualizado exitosamente',
            'product' => $product->fresh()
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product): JsonResponse
    {
        // Opcional: Eliminar imágenes asociadas
        $product->images()->delete();
        
        $product->delete();

        return response()->json([], 204);
    }
}
