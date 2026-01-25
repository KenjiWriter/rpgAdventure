<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CharacterController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ForgeController;
use App\Http\Controllers\MissionController;
use App\Http\Controllers\MapController; // Added for MapController

Route::get('/inventory', [InventoryController::class, 'index']);
Route::post('/inventory/move', [InventoryController::class, 'move']);

Route::get('/maps', [MapController::class, 'index']);

// Game logic moved to web.php for Session Auth
