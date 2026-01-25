<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    if (!auth()->check()) {
        return Inertia::render('Landing');
    }

    $user = auth()->user();
    $character = $user->characters()->first();

    if (!$character) {
        return redirect()->route('character.create');
    }

    return Inertia::render('Game', [
        'characterId' => $character->id
    ]);
})->name('home');

Route::get('/create-character', function () {
    if (auth()->user()->characters()->exists()) {
        return redirect()->route('home');
    }
    return Inertia::render('CreateCharacter');
})->middleware(['auth', 'verified'])->name('character.create');

Route::get('/map', function () {
    return Inertia::render('WorldMap');
})->middleware(['auth', 'verified'])->name('map');

Route::get('/quests', function () {
    return Inertia::render('QuestLog');
})->middleware(['auth', 'verified'])->name('quests');

Route::post('/character', [\App\Http\Controllers\CharacterController::class, 'store'])
    ->middleware(['auth', 'verified'])
    ->name('character.store');

Route::get('dashboard', function () {
    return redirect()->route('home');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::get('/character/{id}', [\App\Http\Controllers\CharacterController::class, 'show']);
    Route::get('/inventory', [\App\Http\Controllers\InventoryController::class, 'index']);
    Route::post('/inventory/move', [\App\Http\Controllers\InventoryController::class, 'move']);

    Route::post('/forge/upgrade', [\App\Http\Controllers\ForgeController::class, 'upgrade']);

    Route::post('/mission/start', [\App\Http\Controllers\MissionController::class, 'start']);
    Route::post('/mission/claim', [\App\Http\Controllers\MissionController::class, 'claim']);
    Route::get('/mission/active', [\App\Http\Controllers\MissionController::class, 'active']);
});

require __DIR__ . '/settings.php';
