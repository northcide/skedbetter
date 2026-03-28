<?php

namespace App\Models;

use App\Enums\SurfaceType;
use App\Models\Traits\BelongsToLeague;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Field extends Model
{
    use BelongsToLeague, HasFactory, SoftDeletes;

    protected $fillable = [
        'location_id', 'league_id', 'name', 'surface_type',
        'capacity', 'is_lighted', 'is_active', 'sort_order', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'surface_type' => SurfaceType::class,
            'is_lighted' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function scheduleEntries()
    {
        return $this->hasMany(ScheduleEntry::class);
    }
}
