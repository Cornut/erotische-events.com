<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\GoController;
use App\Http\Controllers\Public\CalendarController;
use App\Http\Controllers\Organizer\DashboardController;
use App\Http\Controllers\Organizer\EventController;
use App\Http\Controllers\Organizer\OrganizerProfileController;
use App\Http\Controllers\Organizer\TeacherController as OrganizerTeacherController;
use App\Http\Controllers\Organizer\VenueController as OrganizerVenueController;
use App\Http\Controllers\OrganizerRegistrationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\EventController as PublicEventController;
use App\Http\Controllers\Public\OrganizerController as PublicOrganizerController;
use App\Http\Controllers\Public\TeacherController as PublicTeacherController;
use Illuminate\Support\Facades\Route;

// Home shows the public events listing (same content as /events).
Route::get('/', [PublicEventController::class, 'index'])->name('home');

// Locale switch (DE/EN)
Route::get('/locale/{locale}', function (string $locale) {
    if (in_array($locale, ['de', 'en'], true)) {
        session(['locale' => $locale]);
    }

    return back();
})->name('locale.switch');

// Outbound tracking redirect (published events only); records a click, no IP stored.
Route::get('/go/{event}', [GoController::class, 'redirect'])->name('go');

// Public catalog (published events / approved organizers only)
Route::get('/events', [PublicEventController::class, 'index'])->name('events.index');
Route::get('/events/{slug}', [PublicEventController::class, 'show'])->name('events.show');
Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar');
Route::get('/organizers', [PublicOrganizerController::class, 'index'])->name('organizers.index');
Route::get('/organizers/{slug}', [PublicOrganizerController::class, 'show'])->name('organizers.show');
Route::get('/teachers', [PublicTeacherController::class, 'index'])->name('teachers.index');
Route::get('/teacher/{slug}', [PublicTeacherController::class, 'show'])->name('teachers.show');

// Contact
Route::get('/contact', [ContactController::class, 'create'])->name('contact');
Route::post('/contact', [ContactController::class, 'store']);

Route::middleware('auth')->group(function () {
    // Settings hub with a left-column menu (Profil / Favoriten / Abmelden).
    Route::get('/settings', fn () => redirect()->route('profile.edit'))->name('settings');
    Route::get('/settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Favorites (registered users)
    Route::get('/settings/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/events/{event}/favorite', [FavoriteController::class, 'toggle'])->name('favorites.toggle');

    // Task 4: Organizer self-registration
    Route::get('/organizer/register', [OrganizerRegistrationController::class, 'create'])->name('organizer.register');
    Route::post('/organizer/register', [OrganizerRegistrationController::class, 'store']);

    // Organizer self-service area — manage own profile, events, venues, teachers.
    Route::prefix('organizer')->name('organizer.')->middleware('organizer')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Stammdaten (organizer master data)
        Route::get('/profile', [OrganizerProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [OrganizerProfileController::class, 'update'])->name('profile.update');

        // Events
        Route::get('/events', [EventController::class, 'index'])->name('events.index');
        Route::get('/events/create', [EventController::class, 'create'])->name('events.create');
        Route::post('/events', [EventController::class, 'store'])->name('events.store');
        Route::get('/events/{event}/edit', [EventController::class, 'edit'])->name('events.edit');
        Route::put('/events/{event}', [EventController::class, 'update'])->name('events.update');
        Route::delete('/events/{event}', [EventController::class, 'destroy'])->name('events.destroy');
        Route::post('/events/{event}/submit', [EventController::class, 'submit'])->name('events.submit');

        // Venues
        Route::get('/venues', [OrganizerVenueController::class, 'index'])->name('venues.index');
        Route::get('/venues/create', [OrganizerVenueController::class, 'create'])->name('venues.create');
        Route::post('/venues', [OrganizerVenueController::class, 'store'])->name('venues.store');
        Route::get('/venues/{venue}/edit', [OrganizerVenueController::class, 'edit'])->name('venues.edit');
        Route::put('/venues/{venue}', [OrganizerVenueController::class, 'update'])->name('venues.update');
        Route::delete('/venues/{venue}', [OrganizerVenueController::class, 'destroy'])->name('venues.destroy');

        // Teachers (shared pool: list + create)
        Route::get('/teachers', [OrganizerTeacherController::class, 'index'])->name('teachers.index');
        Route::post('/teachers', [OrganizerTeacherController::class, 'store'])->name('teachers.store');
    });
});

require __DIR__.'/auth.php';
