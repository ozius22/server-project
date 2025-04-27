<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\LanguageController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'authenticate']);
    Route::post('/logout', [AuthController::class, 'unauthenticate'])->middleware('auth:sanctum');
});

Route::apiResource('languages', LanguageController::class);
