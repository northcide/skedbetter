<?php

namespace App\Http\Controllers;

use App\Models\League;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CheckoutController extends Controller
{
    public function success(Request $request, string $league)
    {
        $league = League::where('slug', $league)->firstOrFail();

        // If webhook already fired and league is approved, redirect to onboarding
        if ($league->isApproved()) {
            return redirect()->route('leagues.onboarding', $league->slug);
        }

        return Inertia::render('Checkout/Success', [
            'league' => $league->only('slug', 'name'),
        ]);
    }

    public function cancel(Request $request, string $league)
    {
        $league = League::where('slug', $league)->firstOrFail();

        return Inertia::render('Checkout/Cancel', [
            'league' => $league->only('slug', 'name'),
        ]);
    }

    public function retry(Request $request, string $league)
    {
        $league = League::where('slug', $league)->firstOrFail();

        $priceId = $this->resolvePriceId($league->stripe_plan, 'monthly');

        $checkout = $league->newSubscription('default', $priceId)
            ->trialDays(14)
            ->checkout([
                'success_url' => route('checkout.success', $league->slug) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('checkout.cancel', $league->slug),
            ]);

        return Inertia::location($checkout->url);
    }

    public function status(Request $request, string $league)
    {
        $league = League::where('slug', $league)->firstOrFail();

        return response()->json([
            'approved' => $league->isApproved(),
        ]);
    }

    public function portal(Request $request, string $league)
    {
        $league = League::where('slug', $league)->firstOrFail();

        return $league->redirectToBillingPortal(route('leagues.show', $league->slug));
    }

    protected function resolvePriceId(string $plan, string $period): string
    {
        $plans = config('plans');
        $key = $period === 'annual' ? 'annual_price_id' : 'monthly_price_id';

        return $plans[$plan][$key] ?? $plans['starter'][$key];
    }
}
