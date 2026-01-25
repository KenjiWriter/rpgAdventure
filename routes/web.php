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

Route::post('/character', [\App\Http\Controllers\CharacterController::class, 'store'])
    ->middleware(['auth', 'verified'])
    ->name('character.store');

Route::get('dashboard', function () {
    return redirect()->route('home');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__ . '/settings.php';
