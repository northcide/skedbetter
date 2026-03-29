<?php

namespace App\Http\Controllers\League;

use App\Http\Controllers\Controller;
use App\Models\BookingWindow;
use App\Models\Division;
use App\Services\LeagueContext;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BookingWindowController extends Controller
{
    public function index(string $league)
    {
        $context = app(LeagueContext::class);
        $leagueModel = $context->league();

        $windows = BookingWindow::with('divisions:id,name,booking_window_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $unassignedDivisions = Division::whereNull('booking_window_id')
            ->orderBy('name')
            ->get(['id', 'name']);

        $allDivisions = Division::orderBy('name')->get(['id', 'name', 'booking_window_id']);

        return Inertia::render('Leagues/BookingWindows/Index', [
            'league' => $leagueModel,
            'windows' => $windows,
            'unassignedDivisions' => $unassignedDivisions,
            'allDivisions' => $allDivisions,
            'userRole' => $context->userRole(),
        ]);
    }

    public function store(Request $request, string $league)
    {
        $context = app(LeagueContext::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'window_type' => 'required|in:calendar,rolling',
            'opens_date' => 'nullable|date|required_if:window_type,calendar',
            'rolling_days' => 'nullable|integer|min:1|required_if:window_type,rolling',
            'division_ids' => 'nullable|array',
            'division_ids.*' => 'exists:divisions,id',
        ]);

        $divisionIds = $validated['division_ids'] ?? [];
        unset($validated['division_ids']);
        $validated['league_id'] = $context->league()->id;
        $validated['sort_order'] = BookingWindow::count();

        $window = BookingWindow::create($validated);

        if (!empty($divisionIds)) {
            Division::whereIn('id', $divisionIds)->update(['booking_window_id' => $window->id]);
        }

        return back()->with('success', "Booking window \"{$window->name}\" created.");
    }

    public function update(Request $request, string $league, BookingWindow $bookingWindow)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'window_type' => 'required|in:calendar,rolling',
            'opens_date' => 'nullable|date|required_if:window_type,calendar',
            'rolling_days' => 'nullable|integer|min:1|required_if:window_type,rolling',
            'division_ids' => 'nullable|array',
            'division_ids.*' => 'exists:divisions,id',
        ]);

        $divisionIds = $validated['division_ids'] ?? [];
        unset($validated['division_ids']);

        $bookingWindow->update($validated);

        // Unassign all divisions from this window, then reassign selected ones
        Division::where('booking_window_id', $bookingWindow->id)->update(['booking_window_id' => null]);
        if (!empty($divisionIds)) {
            Division::whereIn('id', $divisionIds)->update(['booking_window_id' => $bookingWindow->id]);
        }

        return back()->with('success', "Booking window \"{$bookingWindow->name}\" updated.");
    }

    public function destroy(Request $request, string $league, BookingWindow $bookingWindow)
    {
        // Unassign divisions first
        Division::where('booking_window_id', $bookingWindow->id)->update(['booking_window_id' => null]);
        $name = $bookingWindow->name;
        $bookingWindow->delete();

        return back()->with('success', "Booking window \"{$name}\" deleted.");
    }
}
