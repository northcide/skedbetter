<?php

namespace App\Models;

use App\Enums\ScheduleEntryStatus;
use App\Enums\ScheduleEntryType;
use App\Models\Traits\BelongsToLeague;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScheduleEntry extends Model
{
    use BelongsToLeague, HasFactory, SoftDeletes;

    protected $fillable = [
        'league_id', 'season_id', 'field_id', 'team_id',
        'date', 'start_time', 'end_time',
        'type', 'title', 'notes',
        'created_by', 'updated_by', 'status', 'recurrence_group_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'type' => ScheduleEntryType::class,
            'status' => ScheduleEntryStatus::class,
        ];
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function field()
    {
        return $this->belongsTo(Field::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function recurrenceGroup()
    {
        return $this->belongsTo(RecurrenceGroup::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', '!=', ScheduleEntryStatus::Cancelled);
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('date', $date);
    }

    public function scopeForDateRange($query, $start, $end)
    {
        return $query->whereBetween('date', [$start, $end]);
    }
}
