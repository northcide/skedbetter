<?php

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\AcceptInvitationController;
use App\Http\Controllers\Admin\AdminAuditLogController;
use App\Http\Controllers\Admin\AdminLeagueController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\League\DataImportController;
use App\Http\Controllers\IcalController;
use App\Http\Controllers\LeagueController;
use App\Http\Controllers\League\BlackoutRuleController;
use App\Http\Controllers\League\InvitationController;
use App\Http\Controllers\League\OnboardingController;
use App\Http\Controllers\League\TeamImportController;
use App\Http\Controllers\League\DivisionController;
use App\Http\Controllers\League\FieldController;
use App\Http\Controllers\League\FieldRulesController;
use App\Http\Controllers\League\LocationController;
use App\Http\Controllers\League\AuditLogController;
use App\Http\Controllers\League\ScheduleEntryController;
use App\Http\Controllers\League\BookingWindowController;
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
        'plans' => collect(config('plans'))->map(fn ($p, $k) => [
            'slug' => $k,
            'name' => $p['name'],
            'monthly_price' => $p['monthly_price'],
            'annual_price' => $p['annual_price'],
            'limits' => $p['limits'],
        ])->values(),
    ]);
});

// Magic link auth (no auth required)
Route::post('/auth/magic-link', [\App\Http\Controllers\Auth\MagicLinkController::class, 'request'])->name('auth.magic-link.request');
Route::get('/auth/magic/{token}', [\App\Http\Controllers\Auth\MagicLinkController::class, 'verify'])->name('auth.magic-link.verify');

// iCal feed (signed URL, no auth required)
Route::get('/ical/teams/{teamId}', [IcalController::class, 'team'])->name('ical.team');

// Invitation accept (public)
Route::get('/invitations/{token}', [AcceptInvitationController::class, 'show'])->name('invitations.show');
Route::post('/invitations/{token}/accept', [AcceptInvitationController::class, 'accept'])->name('invitations.accept');

Route::get('/dashboard', function () {
    return redirect()->route('leagues.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // Checkout (allowed for unapproved users via EnsureUserApproved middleware)
    Route::get('/checkout/{league}/success', [CheckoutController::class, 'success'])->name('checkout.success');
    Route::get('/checkout/{league}/cancel', [CheckoutController::class, 'cancel'])->name('checkout.cancel');
    Route::get('/checkout/{league}/retry', [CheckoutController::class, 'retry'])->name('checkout.retry');
    Route::get('/checkout/{league}/status', [CheckoutController::class, 'status'])->name('checkout.status');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.mark-read');

    // Admin (superadmin)
    Route::get('/admin/leagues', [AdminLeagueController::class, 'index'])->name('admin.leagues');
    Route::post('/admin/leagues/{league}/approve', [AdminLeagueController::class, 'approve'])->name('admin.leagues.approve');
    Route::delete('/admin/leagues/{league}/reject', [AdminLeagueController::class, 'reject'])->name('admin.leagues.reject');
    Route::post('/admin/leagues/{league}/toggle-active', [AdminLeagueController::class, 'toggleActive'])->name('admin.leagues.toggle-active');
    Route::get('/admin/users', [AdminUserController::class, 'index'])->name('admin.users');
    Route::post('/admin/users/{user}/toggle-active', [AdminUserController::class, 'toggleActive'])->name('admin.users.toggle-active');
    Route::delete('/admin/users/{user}', [AdminUserController::class, 'destroy'])->name('admin.users.destroy');
    Route::get('/admin/audit-log', [AdminAuditLogController::class, 'index'])->name('admin.audit-log');
    Route::get('/admin/settings', [SettingsController::class, 'index'])->name('admin.settings');
    Route::post('/admin/settings', [SettingsController::class, 'update'])->name('admin.settings.update');
    Route::post('/admin/settings/test-email', [SettingsController::class, 'testEmail'])->name('admin.settings.test-email');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // League management (top-level)
    Route::resource('leagues', LeagueController::class)->except(['show']);
    Route::get('/leagues/{league}', [LeagueController::class, 'show'])->name('leagues.show');

    // League-scoped routes (accessible by all league members including coaches)
    Route::prefix('leagues/{league}')
        ->middleware('league')
        ->as('leagues.')
        ->group(function () {
            // Schedule (coaches can view all, create/edit/delete own)
            Route::get('schedule/calendar', [ScheduleEntryController::class, 'calendar'])
                ->name('schedule.calendar');
            Route::resource('schedule', ScheduleEntryController::class)
                ->except(['show'])
                ->parameters(['schedule' => 'scheduleEntry']);
            Route::get('schedule/calendar/events', [ScheduleEntryController::class, 'events'])
                ->name('schedule.events');
            Route::post('schedule/validate', [ScheduleEntryController::class, 'validateEntry'])
                ->name('schedule.validate');
            Route::get('schedule/calendar/resources', [ScheduleEntryController::class, 'resources'])
                ->name('schedule.resources');
            Route::patch('schedule/{scheduleEntry}/move', [ScheduleEntryController::class, 'move'])
                ->name('schedule.move');

            // Team show (coaches can view their own team)
            Route::get('teams/{team}', [TeamController::class, 'show'])->name('teams.show');

            // Manager-only routes
            Route::middleware('league.manager')->group(function () {
                Route::get('billing', [CheckoutController::class, 'portal'])->name('billing');

                Route::resource('seasons', SeasonController::class)->except(['show']);
                Route::resource('divisions', DivisionController::class)->except(['show']);
                Route::post('divisions/bulk', [DivisionController::class, 'bulkStore'])->name('divisions.bulk');
                Route::resource('teams', TeamController::class)->except(['show']);
                Route::post('teams/{team}/send-invite', [TeamController::class, 'sendInvite'])->name('teams.send-invite');
                Route::post('teams/bulk', [TeamController::class, 'bulkStore'])->name('teams.bulk');
                Route::get('teams-import', [TeamImportController::class, 'create'])->name('teams.import');
                Route::post('teams-import', [TeamImportController::class, 'store'])->name('teams.import.store');
                Route::resource('locations', LocationController::class)->except(['show']);
                Route::post('locations/bulk', [LocationController::class, 'bulkStore'])->name('locations.bulk');
                Route::resource('fields', FieldController::class)->except(['index', 'show']);
                Route::get('fields/{field}/rules', [FieldRulesController::class, 'edit'])->name('fields.rules');
                Route::put('fields/{field}/rules', [FieldRulesController::class, 'update'])->name('fields.rules.update');

                Route::get('booking-windows', [BookingWindowController::class, 'index'])->name('booking-windows.index');
                Route::post('booking-windows', [BookingWindowController::class, 'store'])->name('booking-windows.store');
                Route::put('booking-windows/{bookingWindow}', [BookingWindowController::class, 'update'])->name('booking-windows.update');
                Route::delete('booking-windows/{bookingWindow}', [BookingWindowController::class, 'destroy'])->name('booking-windows.destroy');

                Route::get('locations/{location}/fields/create', [FieldController::class, 'create'])
                    ->name('locations.fields.create');
                Route::post('locations/{location}/fields', [FieldController::class, 'store'])
                    ->name('locations.fields.store');

                Route::get('schedule/bulk', [ScheduleEntryController::class, 'bulk'])
                    ->name('schedule.bulk');
                Route::post('schedule/bulk', [ScheduleEntryController::class, 'bulkStore'])
                    ->name('schedule.bulk.store');
                Route::post('schedule/{scheduleEntry}/cancel-series', [ScheduleEntryController::class, 'cancelSeries'])
                    ->name('schedule.cancel-series');

                Route::get('setup', [OnboardingController::class, 'index'])->name('onboarding');
                Route::post('setup/save', [OnboardingController::class, 'save'])->name('onboarding.save');

                Route::get('members', [InvitationController::class, 'index'])->name('members.index');
                Route::post('invitations', [InvitationController::class, 'store'])->name('invitations.store');
                Route::delete('invitations/{invitation}', [InvitationController::class, 'destroy'])->name('invitations.destroy');
                Route::delete('members/{user}', [InvitationController::class, 'removeMember'])->name('members.destroy');
                Route::post('members/{user}/send-magic-link', [InvitationController::class, 'sendMagicLink'])->name('members.send-magic-link');
                Route::post('members/{user}/generate-magic-link', [InvitationController::class, 'generateMagicLink'])->name('members.generate-magic-link');

                Route::get('audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');

                // Data import
                Route::get('data-import', [DataImportController::class, 'create'])->name('data-import');
                Route::get('data-import/template/{type}', [DataImportController::class, 'template'])->name('data-import.template');
                Route::post('data-import/preview', [DataImportController::class, 'preview'])->name('data-import.preview');
                Route::post('data-import/import', [DataImportController::class, 'import'])->name('data-import.import');

                Route::resource('blackouts', BlackoutRuleController::class)
                    ->except(['show'])
                    ->parameters(['blackouts' => 'blackout']);
            });
        });
});

// Stripe webhook (no auth, CSRF excluded in bootstrap/app.php)
Route::post('/stripe/webhook', [\Laravel\Cashier\Http\Controllers\WebhookController::class, 'handleWebhook'])
    ->name('cashier.webhook');

require __DIR__.'/auth.php';
