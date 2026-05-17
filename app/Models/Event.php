<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    public const CATEGORIES = [
        'Music',
        'Sports',
        'Food & Drink',
        'Arts',
        'Education',
        'Community',
    ];

    public const STATUSES = [
        'draft',
        'published',
        'cancelled',
    ];

    protected $fillable = [
        'organizer_id',
        'title',
        'description',
        'category',
        'location',
        'event_date',
        'capacity',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'datetime',
            'capacity' => 'integer',
        ];
    }

    // Define relationship to User (Organizer)
    public function organizer()
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }
}
