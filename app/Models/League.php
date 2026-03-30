<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Cashier\Billable;

class League extends Model
{
    use HasFactory, SoftDeletes, Billable;

    protected $fillable = [
        'name', 'slug', 'description', 'timezone', 'logo_path',
        'contact_email', 'settings', 'is_active', 'approved_at', 'requested_by',
        'stripe_id', 'pm_type', 'pm_last_four', 'trial_ends_at', 'stripe_plan',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_active' => 'boolean',
            'approved_at' => 'datetime',
            'trial_ends_at' => 'datetime',
        ];
    }

    /**
     * Get the plan limits for this league's current plan.
     */
    public function planLimits(): array
    {
        $plans = config('plans');
        return $plans[$this->stripe_plan]['limits'] ?? $plans['pro']['limits'];
    }

    /**
     * Check if the league has an active subscription or trial.
     */
    public function hasActivePlan(): bool
    {
        // Grandfathered leagues (approved without Stripe) always have access
        if (!$this->stripe_id && $this->isApproved()) {
            return true;
        }

        return $this->subscribed('default') || $this->onTrial();
    }

    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    public function isPending(): bool
    {
        return $this->approved_at === null;
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    protected static function booted(): void
    {
        static::creating(function (League $league) {
            if (empty($league->slug)) {
                $base = Str::slug($league->name);
                $slug = $base;
                $i = 1;
                while (static::withTrashed()->where('slug', $slug)->exists()) {
                    $slug = "{$base}-{$i}";
                    $i++;
                }
                $league->slug = $slug;
            }
        });
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'league_user')
            ->withPivot('role', 'invited_at', 'accepted_at')
            ->withTimestamps();
    }

    public function seasons()
    {
        return $this->hasMany(Season::class);
    }

    public function currentSeason()
    {
        return $this->hasOne(Season::class)->where('is_current', true);
    }

    public function divisions()
    {
        return $this->hasMany(Division::class);
    }

    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    public function locations()
    {
        return $this->hasMany(Location::class);
    }

    public function fields()
    {
        return $this->hasMany(Field::class);
    }

    public function scheduleEntries()
    {
        return $this->hasMany(ScheduleEntry::class);
    }

    public function blackoutRules()
    {
        return $this->hasMany(BlackoutRule::class);
    }

    public function schedulingConstraints()
    {
        return $this->hasMany(SchedulingConstraint::class);
    }
}
