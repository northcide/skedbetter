<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FieldTimeSlot extends Model
{
    protected $fillable = [
        'field_id', 'day_of_week', 'start_time', 'end_time', 'label', 'sort_order',
    ];

    public function field()
    {
        return $this->belongsTo(Field::class);
    }
}
