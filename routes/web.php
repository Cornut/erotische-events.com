<?php

use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\Organizer\DashboardController;
use App\Http\Controllers\Organizer\EventController;
use App\Http\Controllers\OrganizerRegistrationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\EventController as PublicEventController;
use App\Http\Controllers\Public\OrganizerController as PublicOrganizerController;
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
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Public catalog (published events / approved organizers only)
Route::get('/events', [PublicEventController::class, 'index'])->name('events.index');
Route::get('/events/{slug}', [PublicEventController::class, 'show'])->name('events.show');
Route::get('/organizers/{slug}', [PublicOrganizerController::class, 'show'])->name('organizers.show');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Favorites (registered users)
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/events/{event}/favorite', [FavoriteController::class, 'toggle'])->name('favorites.toggle');

    // Task 4: Organizer self-registration
    Route::get('/organizer/register', [OrganizerRegistrationController::class, 'create'])->name('organizer.register');
    Route::post('/organizer/register', [OrganizerRegistrationController::class, 'store']);

    // Task 5: Organizer dashboard + event lifecycle
    Route::prefix('organizer')->name('organizer.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/events', [EventController::class, 'store'])->name('events.store');
        Route::put('/events/{event}', [EventController::class, 'update'])->name('events.update');
        Route::post('/events/{event}/submit', [EventController::class, 'submit'])->name('events.submit');
    });
});

require __DIR__.'/auth.php';
