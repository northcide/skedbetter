<?php

namespace App\Models;

use App\Models\Traits\BelongsToLeague;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Season extends Model
{
    use BelongsToLeague, HasFactory;

    protected $fillable = [
        'league_id', 'name', 'start_date', 'end_date', 'is_current',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_current' => 'boolean',
        ];
    }

    public function divisions()
    {
        return $this->hasMany(Division::class);
    }

    public function scheduleEntries()
    {
        return $this->hasMany(ScheduleEntry::class);
    }

    public function schedulingConstraints()
    {
        return $this->hasMany(SchedulingConstraint::class);
    }
}
