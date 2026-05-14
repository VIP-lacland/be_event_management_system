<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_event_details_without_changing_status(): void
    {
        $organizer = User::factory()->create([
            'role' => 'organizer',
        ]);

        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Original Event',
            'description' => 'Original description',
            'category' => 'Music',
            'location' => 'Old Venue',
            'event_date' => '2026-06-01 18:00:00',
            'capacity' => 100,
            'status' => 'draft',
        ]);

        $payload = [
            'title' => 'Updated Event',
            'description' => 'Updated description',
            'category' => 'Arts',
            'location' => 'New Venue',
            'event_date' => '2026-06-20 11:00:00',
            'capacity' => 250,
        ];

        $response = $this->putJson("/api/organizer/events/{$event->id}", $payload);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Event updated successfully')
            ->assertJsonPath('event.title', $payload['title'])
            ->assertJsonPath('event.category', $payload['category'])
            ->assertJsonPath('event.event_date', '2026-06-20T11:00:00.000000Z')
            ->assertJsonPath('event.status', 'draft');

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'title' => $payload['title'],
            'description' => $payload['description'],
            'category' => $payload['category'],
            'location' => $payload['location'],
            'event_date' => $payload['event_date'],
            'capacity' => $payload['capacity'],
            'status' => 'draft',
        ]);
    }

    public function test_it_rejects_invalid_event_category(): void
    {
        $organizer = User::factory()->create([
            'role' => 'organizer',
        ]);

        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Original Event',
            'description' => 'Original description',
            'category' => 'Music',
            'location' => 'Old Venue',
            'event_date' => '2026-06-01 18:00:00',
            'capacity' => 100,
            'status' => 'draft',
        ]);

        $response = $this->putJson("/api/organizer/events/{$event->id}", [
            'title' => 'Updated Event',
            'description' => 'Updated description',
            'category' => 'Gaming',
            'location' => 'New Venue',
            'event_date' => '2026-06-20 11:00:00',
            'capacity' => 250,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category');
    }

    public function test_it_updates_only_event_status_with_dedicated_endpoint(): void
    {
        $organizer = User::factory()->create([
            'role' => 'organizer',
        ]);

        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Original Event',
            'description' => 'Original description',
            'category' => 'Music',
            'location' => 'Old Venue',
            'event_date' => '2026-06-01 18:00:00',
            'capacity' => 100,
            'status' => 'draft',
        ]);

        $response = $this->patchJson("/api/organizer/events/{$event->id}/status", [
            'status' => 'published',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Event status updated successfully')
            ->assertJsonPath('event.status', 'published')
            ->assertJsonPath('event.title', 'Original Event');

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'status' => 'published',
            'title' => 'Original Event',
        ]);
    }
}
