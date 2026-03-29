<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlackoutRuleScope extends Model
{
    protected $fillable = ['blackout_rule_id', 'scope_type', 'scope_id'];

    public function blackoutRule()
    {
        return $this->belongsTo(BlackoutRule::class);
    }
}
