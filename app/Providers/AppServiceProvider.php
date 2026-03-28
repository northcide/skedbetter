<?php

namespace App\Providers;

use App\Models\ScheduleEntry;
use App\Observers\ScheduleEntryObserver;
use App\Services\LeagueContext;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LeagueContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
        ScheduleEntry::observe(ScheduleEntryObserver::class);
    }
}
