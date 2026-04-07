<?php

namespace App\Models;

use App\Models\Traits\BelongsToLeague;
use Illuminate\Database\Eloquent\Model;

class DivisionAvailabilityRule extends Model
{
    protected $fillable = [
        'division_id', 'day_of_week', 'all_day', 'start_time', 'end_time',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'all_day' => 'boolean',
    ];

    public function division()
    {
        return $this->belongsTo(Division::class);
    }
}
