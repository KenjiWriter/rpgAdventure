<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/character/{id}', [CharacterController::class, 'show']);
    Route::get('/inventory', [InventoryController::class, 'index']);
    Route::post('/inventory/move', [InventoryController::class, 'move']);

    Route::post('/forge/upgrade', [ForgeController::class, 'upgrade']);

    Route::post('/mission/start', [MissionController::class, 'start']);
    Route::post('/mission/claim', [MissionController::class, 'claim']);
    Route::get('/mission/active', [MissionController::class, 'active']);
});
