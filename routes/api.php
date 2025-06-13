<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AuthController;
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
