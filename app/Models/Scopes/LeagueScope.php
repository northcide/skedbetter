<?php

namespace App\Models\Scopes;

use App\Services\LeagueContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class LeagueScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(LeagueContext::class);

        if ($context->hasLeague()) {
            $builder->where($model->getTable() . '.league_id', $context->league()->id);
        }
    }
}
