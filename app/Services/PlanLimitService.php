<?php

namespace App\Services;

use App\Models\League;

class PlanLimitService
{
    public function canAddTeam(League $league): bool
    {
        $limit = $league->planLimits()['teams'];
        return $limit === null || $league->teams()->count() < $limit;
    }

    public function canAddField(League $league): bool
    {
        $limit = $league->planLimits()['fields'];
        return $limit === null || $league->fields()->count() < $limit;
    }

    public function canAddDivision(League $league): bool
    {
        $limit = $league->planLimits()['divisions'];
        return $limit === null || $league->divisions()->count() < $limit;
    }

    public function limitsForLeague(League $league): array
    {
        $limits = $league->planLimits();
        return [
            'teams' => ['limit' => $limits['teams'], 'used' => $league->teams()->count()],
            'fields' => ['limit' => $limits['fields'], 'used' => $league->fields()->count()],
            'divisions' => ['limit' => $limits['divisions'], 'used' => $league->divisions()->count()],
        ];
    }
}
