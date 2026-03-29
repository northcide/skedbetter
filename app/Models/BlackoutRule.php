<?php

namespace App\Models;

use App\Enums\BlackoutRecurrence;
use App\Models\Traits\BelongsToLeague;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlackoutRule extends Model
{
    use BelongsToLeague, HasFactory, SoftDeletes;

    protected $fillable = [
        'league_id', 'scope_type', 'name', 'reason',
        'start_date', 'end_date', 'start_time', 'end_time',
        'recurrence', 'day_of_week', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'recurrence' => BlackoutRecurrence::class,
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeEntries()
    {
        return $this->hasMany(BlackoutRuleScope::class);
    }

    public function scopeIds(): array
    {
        return $this->scopeEntries()->pluck('scope_id')->toArray();
    }

    public function appliesToField(int $fieldId): bool
    {
        if ($this->scope_type === 'league') return true;

        $scopeIds = $this->scopeIds();

        if ($this->scope_type === 'field') {
            return in_array($fieldId, $scopeIds);
        }

        if ($this->scope_type === 'location') {
            $locationId = \DB::table('fields')->where('id', $fieldId)->value('location_id');
            return in_array($locationId, $scopeIds);
        }

        return false;
    }
}
