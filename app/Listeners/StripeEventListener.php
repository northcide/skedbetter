<?php

namespace App\Listeners;

use App\Models\AuditLog;
use App\Models\League;
use App\Models\User;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookReceived;

class StripeEventListener
{
    public function handle(WebhookReceived $event): void
    {
        $type = $event->payload['type'] ?? '';

        match ($type) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($event->payload),
            'customer.subscription.updated' => $this->handleSubscriptionUpdated($event->payload),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event->payload),
            'invoice.payment_failed' => $this->handlePaymentFailed($event->payload),
            default => null,
        };
    }

    protected function handleCheckoutCompleted(array $payload): void
    {
        $stripeCustomerId = $payload['data']['object']['customer'] ?? null;
        if (!$stripeCustomerId) return;

        $league = League::where('stripe_id', $stripeCustomerId)->first();
        if (!$league) return;

        // Auto-approve league
        if (!$league->approved_at) {
            $league->update(['approved_at' => now(), 'is_active' => true]);
        }

        // Auto-approve the requesting user
        $user = User::find($league->requested_by);
        if ($user) {
            if (!$user->approved_at) {
                $user->update(['approved_at' => now(), 'email_verified_at' => now()]);
            }

            // Ensure user is attached as league_admin
            if (!$user->leagues()->where('leagues.id', $league->id)->exists()) {
                $user->leagues()->attach($league->id, [
                    'role' => 'league_admin',
                    'accepted_at' => now(),
                ]);
            }
        }

        AuditLog::withoutGlobalScopes()->create([
            'league_id' => $league->id,
            'user_id' => $league->requested_by,
            'action' => 'league_auto_approved',
            'auditable_type' => League::class,
            'auditable_id' => $league->id,
            'new_values' => ['method' => 'stripe_checkout', 'plan' => $league->stripe_plan],
        ]);
    }

    protected function handleSubscriptionUpdated(array $payload): void
    {
        $stripeCustomerId = $payload['data']['object']['customer'] ?? null;
        if (!$stripeCustomerId) return;

        $league = League::where('stripe_id', $stripeCustomerId)->first();
        if (!$league) return;

        $status = $payload['data']['object']['status'] ?? '';
        $priceId = $payload['data']['object']['items']['data'][0]['price']['id'] ?? null;

        // Update plan based on price ID
        if ($priceId) {
            foreach (config('plans') as $slug => $plan) {
                if ($priceId === $plan['monthly_price_id'] || $priceId === $plan['annual_price_id']) {
                    $league->update(['stripe_plan' => $slug]);
                    break;
                }
            }
        }

        // Reactivate if coming back from past_due
        if ($status === 'active' && !$league->is_active) {
            $league->update(['is_active' => true]);
        }
    }

    protected function handleSubscriptionDeleted(array $payload): void
    {
        $stripeCustomerId = $payload['data']['object']['customer'] ?? null;
        if (!$stripeCustomerId) return;

        $league = League::where('stripe_id', $stripeCustomerId)->first();
        if (!$league) return;

        $league->update(['is_active' => false]);

        AuditLog::withoutGlobalScopes()->create([
            'league_id' => $league->id,
            'user_id' => $league->requested_by,
            'action' => 'subscription_cancelled',
            'auditable_type' => League::class,
            'auditable_id' => $league->id,
            'new_values' => ['plan' => $league->stripe_plan],
        ]);
    }

    protected function handlePaymentFailed(array $payload): void
    {
        $stripeCustomerId = $payload['data']['object']['customer'] ?? null;
        if (!$stripeCustomerId) return;

        $league = League::where('stripe_id', $stripeCustomerId)->first();
        if (!$league) return;

        AuditLog::withoutGlobalScopes()->create([
            'league_id' => $league->id,
            'user_id' => $league->requested_by,
            'action' => 'payment_failed',
            'auditable_type' => League::class,
            'auditable_id' => $league->id,
            'new_values' => ['plan' => $league->stripe_plan],
        ]);
    }
}
