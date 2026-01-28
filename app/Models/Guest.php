<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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

    protected static function booted(): void
    {
        static::creating(function (Guest $guest) {
            if (! $guest->invitation_token_hash) {
                $guest->invitation_token_hash = self::hashInvitationToken(Str::random(40));
            }
        });
    }

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'has_plus_one' => 'boolean',
        ];
    }

    public static function hashInvitationToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public function regenerateInvitationToken(): string
    {
        $token = Str::random(40);

        $this->invitation_token_hash = self::hashInvitationToken($token);
        $this->save();

        return $token;
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
