<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    // For prototype, we just get the first character of the first user or a demo ID.
    $user = \App\Models\User::first();
    if (!$user) {
        return 'Please run migration and seed first.';
    }
    $character = $user->characters()->first();

    // Create one if missing (should be there from seeder)
    if (!$character) {
        return 'No character found for user.';
    }

    return Inertia::render('Game', [
        'characterId' => $character->id
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__ . '/settings.php';
