<?php

namespace App\Models;

use App\Enums\ConstraintType;
use App\Models\Traits\BelongsToLeague;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchedulingConstraint extends Model
{
    use BelongsToLeague, HasFactory;

    protected $fillable = [
        'league_id', 'season_id', 'constraint_type', 'value',
        'scope_type', 'scope_id', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'constraint_type' => ConstraintType::class,
            'value' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
