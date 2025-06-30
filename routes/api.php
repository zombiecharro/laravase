<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\ImageController;
use App\Http\middleware\RoleChk;

// Rutas de autenticación
Route::post('auth/signup', [AuthController::class, 'signup']);
Route::post('auth/login', [AuthController::class, 'login']);
Route::post('auth/refresh', [AuthController::class, 'refresh']);

// Ruta para verificar el Bearer token y obtener el usuario autenticado
Route::middleware(['auth:sanctum'])->get('user', [UserController::class, 'verify']);

// Rutas API para User usando apiResource
Route::apiResource('users', UserController::class)
    ->middleware(['auth:sanctum', RoleChk::class . ':admin,staff']);

// Rutas API para UserProfile y Address usando apiResource anidado
Route::middleware('auth:sanctum')->group(function () {
    // Upload de imágenes
    Route::post('upload-image', [ImageController::class, 'upload']);
    
    // Profile - Solo store y update
    Route::post('profile', [UserProfileController::class, 'store']);
    Route::put('profile', [UserProfileController::class, 'update']); // Sin {id}
    
    // Address - Solo store y update  
    Route::post('address', [AddressController::class, 'store']);
    Route::put('address', [AddressController::class, 'update']); // Sin {id}
});