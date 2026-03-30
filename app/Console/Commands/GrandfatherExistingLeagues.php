<?php

namespace App\Console\Commands;

use App\Models\League;
use Illuminate\Console\Command;

class GrandfatherExistingLeagues extends Command
{
    protected $signature = 'leagues:grandfather';
    protected $description = 'Set existing approved leagues to Pro plan (grandfathered, no Stripe customer)';

    public function handle(): int
    {
        $count = League::whereNotNull('approved_at')
            ->whereNull('stripe_id')
            ->whereNull('stripe_plan')
            ->update(['stripe_plan' => 'pro']);

        $this->info("Grandfathered {$count} existing league(s) to Pro plan.");

        return Command::SUCCESS;
    }
}
