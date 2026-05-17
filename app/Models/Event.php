<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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

    protected $casts = [
        'event_date' => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────

    // Event thuộc về 1 organizer (User)
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    // Event có nhiều registrations
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    // ── Scopes ────────────────────────────────────────────────

    // Chỉ lấy event đã published
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    // Chỉ lấy event sắp diễn ra
    public function scopeUpcoming($query)
    {
        return $query->where('event_date', '>', now());
    }

    // ── Helpers ───────────────────────────────────────────────

    // Số chỗ còn lại
    public function getRemainingCapacityAttribute(): int
    {
        $confirmed = $this->registrations()->where('status', 'confirmed')->count();
        return max(0, $this->capacity - $confirmed);
    }

    // Event có còn chỗ không
    public function getIsFullAttribute(): bool
    {
        return $this->remaining_capacity === 0;
    }
}
