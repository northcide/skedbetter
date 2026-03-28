<?php

use App\Http\Controllers\LeagueController;
use App\Http\Controllers\League\DivisionController;
use App\Http\Controllers\League\FieldController;
use App\Http\Controllers\League\LocationController;
use App\Http\Controllers\League\SeasonController;
use App\Http\Controllers\League\TeamController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return redirect()->route('leagues.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // League management (top-level)
    Route::resource('leagues', LeagueController::class)->except(['show']);
    Route::get('/leagues/{league}', [LeagueController::class, 'show'])->name('leagues.show');

    // League-scoped routes
    Route::prefix('leagues/{league}')
        ->middleware('league')
        ->as('leagues.')
        ->group(function () {
            Route::resource('seasons', SeasonController::class)->except(['show']);
            Route::resource('divisions', DivisionController::class)->except(['show']);
            Route::resource('teams', TeamController::class);
            Route::resource('locations', LocationController::class)->except(['show']);
            Route::resource('fields', FieldController::class)->except(['index', 'show']);

            // Nested: fields under locations
            Route::get('locations/{location}/fields/create', [FieldController::class, 'create'])
                ->name('locations.fields.create');
            Route::post('locations/{location}/fields', [FieldController::class, 'store'])
                ->name('locations.fields.store');
        });
});

require __DIR__.'/auth.php';
