<?php

namespace App\Providers;

use App\Listeners\StripeEventListener;
use App\Mail\Transport\MicrosoftGraphTransport;
use App\Models\League;
use App\Models\ScheduleEntry;
use App\Models\Setting;
use App\Observers\ScheduleEntryObserver;
use App\Services\LeagueContext;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookReceived;

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
        Cashier::useCustomerModel(League::class);
        Event::listen(WebhookReceived::class, StripeEventListener::class);

        // Register Microsoft Graph mail transport
        Mail::extend('microsoft-graph', function () {
            return new MicrosoftGraphTransport(
                Setting::get('graph_tenant_id', ''),
                Setting::get('graph_client_id', ''),
                Setting::get('graph_client_secret', ''),
            );
        });

        // Apply database mail settings at runtime
        $this->app->booted(function () {
            try {
                $mailer = Setting::get('mail_mailer', 'smtp');
                $from = Setting::get('mail_from_address');
                $fromName = Setting::get('mail_from_name', 'SkedBetter');

                if ($from) {
                    config(['mail.from.address' => $from, 'mail.from.name' => $fromName]);
                }

                if ($mailer === 'microsoft-graph') {
                    config(['mail.default' => 'microsoft-graph']);
                    config(['mail.mailers.microsoft-graph' => ['transport' => 'microsoft-graph']]);
                } elseif ($mailer === 'smtp') {
                    config(['mail.default' => 'smtp']);
                    $host = Setting::get('smtp_host');
                    if ($host) {
                        config([
                            'mail.mailers.smtp.host' => $host,
                            'mail.mailers.smtp.port' => Setting::get('smtp_port', '587'),
                            'mail.mailers.smtp.username' => Setting::get('smtp_username'),
                            'mail.mailers.smtp.password' => Setting::get('smtp_password'),
                            'mail.mailers.smtp.encryption' => Setting::get('smtp_encryption', 'tls'),
                        ]);
                    }
                }
            } catch (\Exception $e) {
                // Settings table may not exist yet during migrations
            }
        });
    }
}
