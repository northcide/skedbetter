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
        <meta property="og:image" content="{{ config('app.url') }}/images/og-image.png">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:locale" content="en_US">

        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="SkedBetter - Field Scheduling Made Simple">
        <meta name="twitter:description" content="The modern scheduling platform for sports leagues. Manage fields, teams, and time slots with conflict detection and zero double-bookings.">
        <meta name="twitter:image" content="{{ config('app.url') }}/images/og-image.png">

        <link rel="canonical" href="{{ config('app.url') }}">

        <!-- Favicon & Icons -->
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/icon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <link rel="manifest" href="/site.webmanifest">
        <meta name="theme-color" content="#1e3a5f">

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
