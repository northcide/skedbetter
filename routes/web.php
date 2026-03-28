<?php

use App\Http\Controllers\AcceptInvitationController;
use App\Http\Controllers\IcalController;
use App\Http\Controllers\LeagueController;
use App\Http\Controllers\League\BlackoutRuleController;
use App\Http\Controllers\League\InvitationController;
use App\Http\Controllers\League\OnboardingController;
use App\Http\Controllers\League\TeamImportController;
use App\Http\Controllers\League\DivisionController;
use App\Http\Controllers\League\FieldController;
use App\Http\Controllers\League\LocationController;
use App\Http\Controllers\League\ScheduleEntryController;
use App\Http\Controllers\League\SeasonController;
use App\Http\Controllers\League\TeamController;
use App\Http\Controllers\NotificationController;
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

// iCal feed (signed URL, no auth required)
Route::get('/ical/teams/{teamId}', [IcalController::class, 'team'])->name('ical.team');

// Invitation accept (public)
Route::get('/invitations/{token}', [AcceptInvitationController::class, 'show'])->name('invitations.show');
Route::post('/invitations/{token}/accept', [AcceptInvitationController::class, 'accept'])->name('invitations.accept');

Route::get('/dashboard', function () {
    return redirect()->route('leagues.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.mark-read');

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
            Route::get('teams-import', [TeamImportController::class, 'create'])->name('teams.import');
            Route::post('teams-import', [TeamImportController::class, 'store'])->name('teams.import.store');
            Route::resource('locations', LocationController::class)->except(['show']);
            Route::resource('fields', FieldController::class)->except(['index', 'show']);

            // Nested: fields under locations
            Route::get('locations/{location}/fields/create', [FieldController::class, 'create'])
                ->name('locations.fields.create');
            Route::post('locations/{location}/fields', [FieldController::class, 'store'])
                ->name('locations.fields.store');

            // Schedule entries
            Route::get('schedule/calendar', [ScheduleEntryController::class, 'calendar'])
                ->name('schedule.calendar');
            Route::resource('schedule', ScheduleEntryController::class)
                ->except(['show'])
                ->parameters(['schedule' => 'scheduleEntry']);

            // Calendar API endpoints
            Route::get('schedule/calendar/events', [ScheduleEntryController::class, 'events'])
                ->name('schedule.events');
            Route::get('schedule/calendar/resources', [ScheduleEntryController::class, 'resources'])
                ->name('schedule.resources');
            Route::patch('schedule/{scheduleEntry}/move', [ScheduleEntryController::class, 'move'])
                ->name('schedule.move');
            Route::get('schedule/bulk', [ScheduleEntryController::class, 'bulk'])
                ->name('schedule.bulk');
            Route::post('schedule/bulk', [ScheduleEntryController::class, 'bulkStore'])
                ->name('schedule.bulk.store');
            Route::post('schedule/{scheduleEntry}/cancel-series', [ScheduleEntryController::class, 'cancelSeries'])
                ->name('schedule.cancel-series');

            // Onboarding wizard
            Route::get('setup', [OnboardingController::class, 'index'])->name('onboarding');
            Route::post('setup/season', [OnboardingController::class, 'storeSeason'])->name('onboarding.season');
            Route::post('setup/location', [OnboardingController::class, 'storeLocation'])->name('onboarding.location');
            Route::post('setup/division', [OnboardingController::class, 'storeDivision'])->name('onboarding.division');
            Route::post('setup/teams', [OnboardingController::class, 'storeTeams'])->name('onboarding.teams');
            Route::post('setup/complete', [OnboardingController::class, 'complete'])->name('onboarding.complete');

            // Members & invitations
            Route::get('members', [InvitationController::class, 'index'])->name('members.index');
            Route::post('invitations', [InvitationController::class, 'store'])->name('invitations.store');
            Route::delete('invitations/{invitation}', [InvitationController::class, 'destroy'])->name('invitations.destroy');
            Route::delete('members/{user}', [InvitationController::class, 'removeMember'])->name('members.destroy');

            // Blackout rules
            Route::resource('blackouts', BlackoutRuleController::class)
                ->except(['show'])
                ->parameters(['blackouts' => 'blackout']);
        });
});

require __DIR__.'/auth.php';
