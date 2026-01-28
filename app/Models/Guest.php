<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guest extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'tags',
        'has_plus_one',
        'dietary_restrictions',
        'preferred_meal',
        'attendance_status',
        'notes',
        'invitation_token_hash',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'has_plus_one' => 'boolean',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
