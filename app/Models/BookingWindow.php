<?php

namespace App\Models;

use App\Models\Traits\BelongsToLeague;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class BookingWindow extends Model
{
    use BelongsToLeague;

    protected $fillable = [
        'league_id', 'name', 'window_type', 'opens_date', 'rolling_days', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'opens_date' => 'date',
        ];
    }

    public function divisions()
    {
        return $this->hasMany(Division::class);
    }

    public function isOpenForDate(string $date): bool
    {
        if ($this->window_type === 'calendar') {
            return $this->opens_date && Carbon::today()->gte($this->opens_date);
        }

        if ($this->window_type === 'rolling') {
            if (!$this->rolling_days) return true;
            $daysAhead = (int) Carbon::today()->diffInDays(Carbon::parse($date), false);
            return $daysAhead <= $this->rolling_days;
        }

        return true;
    }

    public function opensDescription(): string
    {
        if ($this->window_type === 'calendar' && $this->opens_date) {
            return 'Opens ' . $this->opens_date->format('M j, Y');
        }
        if ($this->window_type === 'rolling' && $this->rolling_days) {
            return "{$this->rolling_days} days ahead";
        }
        return 'Open';
    }
}
