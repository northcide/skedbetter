<?php

namespace App\Models\Traits;

use App\Models\League;
use App\Models\Scopes\LeagueScope;
use App\Services\LeagueContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToLeague
{
    public static function bootBelongsToLeague(): void
    {
        static::addGlobalScope(new LeagueScope);

        static::creating(function ($model) {
            if (empty($model->league_id)) {
                $context = app(LeagueContext::class);
                if ($context->hasLeague()) {
                    $model->league_id = $context->league()->id;
                }
            }
        });
    }

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }
}
