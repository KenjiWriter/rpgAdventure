<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

// Secured Game Routes
Route::middleware(['auth', 'verified', \App\Http\Middleware\EnsureCharacterSelected::class])->group(function () {
    Route::get('/', function () {
        // "Home" logic re-check
        // If we are here, we have a character due to middleware.
        $character = auth()->user()->characters()->first();
        return Inertia::render('Game', [
            'characterId' => $character->id
        ]);
    })->name('home');

    Route::get('/map', function () {
        $character = auth()->user()->characters()->first();
        return Inertia::render('WorldMap', ['characterId' => $character->id]);
    })->name('map');

    Route::get('/quests', function () {
        $character = auth()->user()->characters()->first();
        return Inertia::render('QuestLog', ['characterId' => $character->id]);
    })->name('quests');

    Route::get('dashboard', function () {
        return redirect()->route('home');
    })->name('dashboard');
});

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::get('/character/{id}/logs', [\App\Http\Controllers\CharacterController::class, 'logs']);
    Route::get('/quests', [\App\Http\Controllers\QuestController::class, 'index']);
    Route::post('/quests/{id}/claim', [\App\Http\Controllers\QuestController::class, 'claim']);

    Route::get('/character/{id}', [\App\Http\Controllers\CharacterController::class, 'show']);
    Route::get('/inventory', [\App\Http\Controllers\InventoryController::class, 'index']);
    Route::post('/inventory/move', [\App\Http\Controllers\InventoryController::class, 'move']);

    Route::post('/forge/upgrade', [\App\Http\Controllers\ForgeController::class, 'upgrade']);

    Route::post('/mission/start', [\App\Http\Controllers\MissionController::class, 'start']);
    Route::post('/mission/claim', [\App\Http\Controllers\MissionController::class, 'claim']);
    Route::get('/mission/active', [\App\Http\Controllers\MissionController::class, 'active']);

    Route::get('/maps', [\App\Http\Controllers\MapController::class, 'index']);
});

require __DIR__ . '/settings.php';
