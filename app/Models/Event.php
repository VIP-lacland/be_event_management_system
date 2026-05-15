<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
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

    // Define relationship to User (Organizer)
    public function organizer()
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }
}
