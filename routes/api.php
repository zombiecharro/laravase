<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\ImageController;
use App\Http\Controllers\Api\ProductController;
use App\Http\middleware\RoleChk;

// Rutas de autenticación
Route::post('auth/signup', [AuthController::class, 'signup']);
Route::post('auth/login', [AuthController::class, 'login']);
Route::post('auth/refresh', [AuthController::class, 'refresh']);
Route::get('auth/me', [AuthController::class, 'verify'])->middleware('auth:sanctum');

// Rutas API para User usando apiResource
Route::apiResource('users', UserController::class)
    ->middleware(['auth:sanctum', RoleChk::class . ':admin,staff']);
// Rutas API para productos
Route::middleware('auth:sanctum')->group(function () {
    // Productos - Todos los usuarios pueden ver (index, show)
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{product}', [ProductController::class, 'show']);
    // Productos - Solo admin/staff pueden crear/modificar
    Route::post('products', [ProductController::class, 'store'])
        ->middleware(RoleChk::class . ':admin,staff');
    Route::patch('products/{product}', [ProductController::class, 'update'])
        ->middleware(RoleChk::class . ':admin,staff');
    Route::delete('products/{product}', [ProductController::class, 'destroy'])
        ->middleware(RoleChk::class . ':admin,staff');
});
// Rutas API para UserProfile y Address usando apiResource anidado
Route::middleware('auth:sanctum')->group(function () {
    // Upload de imágenes
    Route::post('upload-image', [ImageController::class, 'upload']);
    // Profile - Solo actualización parcial
    Route::patch('profile', [UserProfileController::class, 'update']); // Sin {id}
    // Address - Solo actualización parcial  
    Route::patch('address', [AddressController::class, 'update']); // Sin {id}
});