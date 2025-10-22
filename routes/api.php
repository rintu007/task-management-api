<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:3,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1'); 


// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    Route::apiResource('tasks', TaskController::class)->middleware('throttle:60,1');

    //Route::get('/tasks/counts', [TaskController::class, 'getCounts']);

});



// Test route
Route::get('/test', function () {
    return response()->json(['message' => 'API is working!']);
});