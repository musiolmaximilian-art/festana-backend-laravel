<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gift extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'price_cents',
        'currency',
        'is_visible',
        'sort_order',
        'is_cash_gift',
        'image_url',
    ];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'is_cash_gift' => 'boolean',
            'price_cents' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
