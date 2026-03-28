<?php

namespace App\Models;

use App\Models\Traits\BelongsToLeague;
use Illuminate\Database\Eloquent\Model;

class RecurrenceGroup extends Model
{
    use BelongsToLeague;

    protected $fillable = ['league_id', 'pattern', 'created_by'];

    protected function casts(): array
    {
        return [
            'pattern' => 'array',
        ];
    }

    public function entries()
    {
        return $this->hasMany(ScheduleEntry::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
