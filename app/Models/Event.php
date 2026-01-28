<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'website_name',
        'title',
        'wedding_date',
        'timezone',
        'public_settings',
    ];

    protected function casts(): array
    {
        return [
            'wedding_date' => 'date',
            'public_settings' => 'array',
        ];
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function gifts()
    {
        return $this->hasMany(Gift::class);
    }

    public function guests()
    {
        return $this->hasMany(Guest::class);
    }
}
