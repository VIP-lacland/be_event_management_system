<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_events_endpoint_returns_only_published_events(): void
    {
        $organizer = User::factory()->create(['role' => 'organizer']);

        Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Published Music Event',
            'description' => 'Visible on the attendee homepage.',
            'category' => 'Music',
            'location' => 'Da Nang',
            'event_date' => now()->addDay(),
            'capacity' => 120,
            'status' => 'published',
        ]);

        Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Draft Sports Event',
            'description' => 'Hidden from public listings.',
            'category' => 'Sports',
            'location' => 'Ha Noi',
            'event_date' => now()->addDays(2),
            'capacity' => 80,
            'status' => 'draft',
        ]);

        $this->getJson('/api/events')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Published Music Event');
    }

    public function test_events_endpoint_filters_by_category(): void
    {
        $organizer = User::factory()->create(['role' => 'organizer']);

        Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Music Festival',
            'category' => 'Music',
            'location' => 'Da Nang',
            'event_date' => now()->addDay(),
            'capacity' => 120,
            'status' => 'published',
        ]);

        Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Food Expo',
            'category' => 'Food & Drink',
            'location' => 'Ho Chi Minh City',
            'event_date' => now()->addDays(2),
            'capacity' => 300,
            'status' => 'published',
        ]);

        $this->getJson('/api/events?category=Food%20%26%20Drink')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Food Expo')
            ->assertJsonPath('data.0.category', 'Food & Drink');
    }
}
