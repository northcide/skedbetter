<?php

namespace App\Services;

use App\Models\League;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherService
{
    /**
     * WMO Weather Code → icon key mapping.
     * https://open-meteo.com/en/docs#weathervariables
     */
    private const WMO_ICONS = [
        0  => 'sunny',          // Clear sky
        1  => 'mostly-sunny',   // Mainly clear
        2  => 'partly-cloudy',  // Partly cloudy
        3  => 'cloudy',         // Overcast
        45 => 'fog',            // Fog
        48 => 'fog',            // Depositing rime fog
        51 => 'drizzle',        // Light drizzle
        53 => 'drizzle',        // Moderate drizzle
        55 => 'drizzle',        // Dense drizzle
        56 => 'drizzle',        // Light freezing drizzle
        57 => 'drizzle',        // Dense freezing drizzle
        61 => 'rain',           // Slight rain
        63 => 'rain',           // Moderate rain
        65 => 'heavy-rain',     // Heavy rain
        66 => 'rain',           // Light freezing rain
        67 => 'heavy-rain',     // Heavy freezing rain
        71 => 'snow',           // Slight snow
        73 => 'snow',           // Moderate snow
        75 => 'snow',           // Heavy snow
        77 => 'snow',           // Snow grains
        80 => 'rain',           // Slight rain showers
        81 => 'rain',           // Moderate rain showers
        82 => 'heavy-rain',     // Violent rain showers
        85 => 'snow',           // Slight snow showers
        86 => 'snow',           // Heavy snow showers
        95 => 'thunderstorm',   // Thunderstorm
        96 => 'thunderstorm',   // Thunderstorm with slight hail
        99 => 'thunderstorm',   // Thunderstorm with heavy hail
    ];

    /**
     * Fetch and cache the 16-day forecast for a league.
     * Returns array keyed by date: ['2026-04-01' => ['high' => 72, 'low' => 55, 'icon' => 'sunny'], ...]
     */
    public function fetchForecast(League $league): array
    {
        if (! $league->weather_latitude || ! $league->weather_longitude) {
            return [];
        }

        $cacheKey = "weather:league:{$league->id}";

        return Cache::remember($cacheKey, 6 * 3600, function () use ($league) {
            return $this->callOpenMeteo($league->weather_latitude, $league->weather_longitude);
        });
    }

    /**
     * Force-refresh forecast for a league (bypass cache).
     */
    public function refreshForecast(League $league): array
    {
        if (! $league->weather_latitude || ! $league->weather_longitude) {
            return [];
        }

        $cacheKey = "weather:league:{$league->id}";
        $forecast = $this->callOpenMeteo($league->weather_latitude, $league->weather_longitude);
        Cache::put($cacheKey, $forecast, 6 * 3600);

        return $forecast;
    }

    /**
     * Get cached forecast for a league (no API call).
     */
    public function getCachedForecast(League $league): array
    {
        return Cache::get("weather:league:{$league->id}", []);
    }

    private function callOpenMeteo(float $lat, float $lng): array
    {
        $response = Http::timeout(10)->get('https://api.open-meteo.com/v1/forecast', [
            'latitude' => $lat,
            'longitude' => $lng,
            'daily' => 'weather_code,temperature_2m_max,temperature_2m_min',
            'temperature_unit' => 'fahrenheit',
            'timezone' => 'auto',
            'forecast_days' => 16,
        ]);

        if (! $response->successful()) {
            Log::warning('WeatherService: Open-Meteo API error', [
                'status' => $response->status(),
                'lat' => $lat,
                'lng' => $lng,
            ]);
            return [];
        }

        $data = $response->json('daily');
        if (! $data || empty($data['time'])) {
            return [];
        }

        $forecast = [];
        foreach ($data['time'] as $i => $date) {
            $wmoCode = $data['weather_code'][$i] ?? 0;
            $forecast[$date] = [
                'high' => round($data['temperature_2m_max'][$i] ?? 0),
                'low' => round($data['temperature_2m_min'][$i] ?? 0),
                'icon' => self::WMO_ICONS[$wmoCode] ?? 'cloudy',
                'code' => $wmoCode,
            ];
        }

        return $forecast;
    }

    /**
     * Geocode a location string (city or US zip code).
     * US zip codes use zippopotam.us; city names use Open-Meteo geocoding.
     * Returns ['latitude' => float, 'longitude' => float, 'name' => string] or null.
     */
    public function geocode(string $query): ?array
    {
        $trimmed = trim($query);

        // US zip code (5 digits, optionally with +4)
        if (preg_match('/^\d{5}(-\d{4})?$/', $trimmed)) {
            $zip = substr($trimmed, 0, 5);
            return $this->geocodeUsZip($zip);
        }

        return $this->geocodeCityName($trimmed);
    }

    private function geocodeUsZip(string $zip): ?array
    {
        $response = Http::timeout(10)->get("https://api.zippopotam.us/us/{$zip}");

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();
        $place = $data['places'][0] ?? null;
        if (! $place) {
            return null;
        }

        return [
            'latitude' => (float) $place['latitude'],
            'longitude' => (float) $place['longitude'],
            'name' => $place['place name'] . ', ' . $place['state abbreviation'],
        ];
    }

    private function geocodeCityName(string $query): ?array
    {
        $cleanQuery = str_replace(',', ' ', $query);

        $response = Http::timeout(10)->get('https://geocoding-api.open-meteo.com/v1/search', [
            'name' => $cleanQuery,
            'count' => 5,
            'language' => 'en',
            'format' => 'json',
        ]);

        if (! $response->successful()) {
            return null;
        }

        $results = $response->json('results');
        if (empty($results)) {
            return null;
        }

        // Prefer US results when available
        $usResult = collect($results)->firstWhere('country_code', 'US');
        $r = $usResult ?? $results[0];

        return [
            'latitude' => $r['latitude'],
            'longitude' => $r['longitude'],
            'name' => trim(($r['name'] ?? '') . ', ' . ($r['admin1'] ?? ''), ', '),
        ];
    }
}
