<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

use App\Http\Controllers\CharacterController;
use App\Http\Controllers\InventoryController;

Route::get('/character/{id}', [CharacterController::class, 'show']);
Route::get('/inventory', [InventoryController::class, 'index']);
Route::post('/inventory/move', [InventoryController::class, 'move']);

use App\Http\Controllers\ForgeController;
Route::post('/forge/upgrade', [ForgeController::class, 'upgrade']);

use App\Http\Controllers\MissionController;
Route::post('/mission/start', [MissionController::class, 'start']);
Route::post('/mission/claim', [MissionController::class, 'claim']);
Route::get('/mission/active', [MissionController::class, 'active']);
