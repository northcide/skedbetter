<?php

namespace App\Services\Scheduling\DTO;

class ScheduleRequest
{
    public function __construct(
        public readonly int $leagueId,
        public readonly int $seasonId,
        public readonly int $fieldId,
        public readonly int $teamId,
        public readonly string $date,
        public readonly string $startTime,
        public readonly string $endTime,
        public readonly ?int $excludeEntryId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            leagueId: $data['league_id'],
            seasonId: $data['season_id'],
            fieldId: $data['field_id'],
            teamId: $data['team_id'],
            date: $data['date'],
            startTime: $data['start_time'],
            endTime: $data['end_time'],
            excludeEntryId: $data['exclude_entry_id'] ?? null,
        );
    }
}
