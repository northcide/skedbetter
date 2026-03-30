<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" translate="no">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="google" content="notranslate">
        <meta name="description" content="SkedBetter is the modern field scheduling platform for sports leagues. Manage fields, teams, and time slots with conflict detection, drag-and-drop calendars, and zero double-bookings.">
        <meta name="keywords" content="field scheduling, sports league scheduling, youth baseball scheduling, field management, team scheduling, practice scheduling, game scheduling">
        <meta name="author" content="SkedBetter">
        <meta name="robots" content="index, follow">

        <!-- Open Graph / Social -->
        <meta property="og:type" content="website">
        <meta property="og:title" content="SkedBetter - Field Scheduling Made Simple">
        <meta property="og:description" content="The modern scheduling platform for sports leagues. Manage fields, teams, and time slots with conflict detection and zero double-bookings.">
        <meta property="og:url" content="{{ config('app.url') }}">
        <meta property="og:site_name" content="SkedBetter">

        <!-- Twitter -->
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="SkedBetter - Field Scheduling Made Simple">
        <meta name="twitter:description" content="The modern scheduling platform for sports leagues. Manage fields, teams, and time slots with conflict detection and zero double-bookings.">

        <link rel="canonical" href="{{ config('app.url') }}">

        <title inertia>{{ config('app.name', 'SkedBetter') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700&dm-serif-display:400,400i&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
