<?php

namespace App\Models;

use App\Models\Traits\BelongsToLeague;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use BelongsToLeague, HasFactory, SoftDeletes;

    protected $fillable = [
        'division_id', 'league_id', 'name', 'color_code',
        'contact_name', 'contact_email', 'contact_phone',
        'contact_name_2', 'contact_email_2',
        'contact_name_3', 'contact_email_3',
        'max_weekly_slots',
    ];

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'team_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function scheduleEntries()
    {
        return $this->hasMany(ScheduleEntry::class);
    }
}
