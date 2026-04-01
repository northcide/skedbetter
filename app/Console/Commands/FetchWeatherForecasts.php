<?php

namespace App\Console\Commands;

use App\Models\League;
use App\Services\WeatherService;
use Illuminate\Console\Command;

class FetchWeatherForecasts extends Command
{
    protected $signature = 'weather:fetch';
    protected $description = 'Fetch weather forecasts for all leagues with coordinates configured';

    public function handle(WeatherService $weather): int
    {
        $leagues = League::whereNotNull('weather_latitude')
            ->whereNotNull('weather_longitude')
            ->where('is_active', true)
            ->whereNotNull('approved_at')
            ->get();

        if ($leagues->isEmpty()) {
            $this->info('No leagues with weather coordinates configured.');
            return Command::SUCCESS;
        }

        $fetched = 0;
        foreach ($leagues as $league) {
            $forecast = $weather->refreshForecast($league);
            $days = count($forecast);
            $this->line("  {$league->name}: {$days} days");
            $fetched++;

            // Brief pause between API calls to be polite
            if ($fetched < $leagues->count()) {
                usleep(200000);
            }
        }

        $this->info("Fetched forecasts for {$fetched} league(s).");
        return Command::SUCCESS;
    }
}
