<?php

namespace App\Models;

use App\Models\Traits\BelongsToLeague;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Division extends Model
{
    use BelongsToLeague, HasFactory, SoftDeletes;

    protected $fillable = [
        'league_id', 'season_id', 'name', 'age_group', 'skill_level', 'max_event_minutes', 'max_weekly_events_per_team', 'booking_window_id', 'sort_order',
    ];

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function bookingWindow()
    {
        return $this->belongsTo(BookingWindow::class);
    }

    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    public function managers()
    {
        return $this->belongsToMany(User::class, 'division_manager_assignments');
    }

    public function allowedFields()
    {
        return $this->belongsToMany(Field::class, 'division_field')
            ->withPivot('max_weekly_slots')
            ->withTimestamps();
    }
}
