<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    // index - obtener órdenes (admin ve todas, usuario ve solo las suyas)
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Construir la consulta base
        $query = Order::with(['user:id,name,email', 'items.product:id,name,price']);
        
        // Si no es admin/staff, solo mostrar sus órdenes
        if (!in_array($user->role, ['admin', 'staff'])) {
            $query->where('user_id', $user->id);
        }
        
        // Filtros opcionales
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('user_id') && in_array($user->role, ['admin', 'staff'])) {
            $query->where('user_id', $request->user_id);
        }
        
        // Ordenar por más recientes primero
        $query->orderBy('created_at', 'desc');
        
        // Paginar resultados
        $orders = $query->paginate($request->get('per_page', 15));
        
        return response()->json([
            'message' => 'Órdenes obtenidas exitosamente',
            'orders' => $orders
        ]);
    }

    // store crear nueva orden con items
    public function store(Request $request)
    {
        // Validar los datos de la orden y los items
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'status' => 'string|in:pending,completed,cancelled',
            
            // Validación para los items de la orden
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            // Usar transacción para asegurar consistencia
            DB::beginTransaction();

            // Calcular el total basado en los items
            $total = 0;
            $orderItemsData = [];

            foreach ($validatedData['items'] as $item) {
                // Obtener el producto para el precio actual
                $product = Product::findOrFail($item['product_id']);
                $itemTotal = $product->price * $item['quantity'];
                $total += $itemTotal;

                $orderItemsData[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $product->price, // Guardar el precio al momento de la compra
                ];
            }

            // Crear la orden
            $order = Order::create([
                'user_id' => $validatedData['user_id'],
                'total' => $total,
                'status' => $validatedData['status'] ?? 'pending',
            ]);

            // Crear los items de la orden
            foreach ($orderItemsData as $itemData) {
                $order->items()->create($itemData);
            }

            DB::commit();

            // Cargar los items y productos relacionados para la respuesta
            $order->load(['items.product', 'user']);

            return response()->json([
                'message' => 'Orden creada exitosamente',
                'order' => $order
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear la orden',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function show(Order $order)
    {
        // Cargar los items y productos relacionados
        $order->load(['items.product', 'user']);

        return response()->json([
            'message' => 'Orden obtenida exitosamente',
            'order' => $order
        ]);
    }
    public function destroy(Order $order)
    {
        try {
            // Eliminar la orden y sus items
            $order->delete();

            return response()->json([
                'message' => 'Orden eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar la orden',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Order $order)
    {
        // Validar los datos de la orden
        $validatedData = $request->validate([
            'status' => 'string|in:pending,completed,cancelled',
        ]);

        // Actualizar el estado de la orden
        if (isset($validatedData['status'])) {
            $order->status = $validatedData['status'];
            $order->save();
        }

        return response()->json([
            'message' => 'Orden actualizada exitosamente',
            'order' => $order->fresh()
        ]);
    }

    // updateItems - actualizar items de una orden (reemplaza todos los items)
    public function updateItems(Request $request, Order $order)
    {
        // 1. Validar que la orden se pueda modificar
        if ($order->status !== 'pending') {
            return response()->json([
                'message' => 'No se pueden modificar items de una orden que no está pendiente'
            ], 422);
        }
        
        // 2. Verificar permisos
        $user = auth()->user();
        if ($order->user_id !== $user->id && !in_array($user->role, ['admin', 'staff'])) {
            return response()->json([
                'message' => 'No tienes permisos para modificar esta orden'
            ], 403);
        }
        
        // 3. Validar datos de entrada
        $validatedData = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);
        
        try {
            DB::beginTransaction();
            
            // 4. Eliminar todos los items existentes
            $order->items()->delete();
            
            // 5. Crear los nuevos items y calcular total
            $total = 0;
            foreach ($validatedData['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                
                // Verificar stock disponible
                if ($product->stock < $item['quantity']) {
                    throw new \Exception("Stock insuficiente para el producto: {$product->name}. Stock disponible: {$product->stock}");
                }
                
                $subtotal = $product->price * $item['quantity'];
                $total += $subtotal;
                
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $product->price, // Usar precio actual del producto
                ]);
            }
            
            // 6. Actualizar el total de la orden
            $order->update(['total' => $total]);
            
            DB::commit();
            
            // 7. Cargar relaciones y devolver respuesta
            $order->load(['items.product', 'user']);
            
            return response()->json([
                'message' => 'Items de la orden actualizados exitosamente',
                'order' => $order
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar los items de la orden',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}