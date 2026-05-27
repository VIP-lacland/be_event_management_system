<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Registration;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private function createEndedEvent(User $organizer): Event
    {
        return Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Past Concert',
            'description' => 'Ended event',
            'category' => 'Music',
            'location' => 'Hanoi',
            'event_date' => now()->subDay(),
            'capacity' => 100,
            'status' => 'published',
        ]);
    }

    private function createFutureEvent(User $organizer): Event
    {
        return Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Upcoming Concert',
            'description' => 'Future event',
            'category' => 'Music',
            'location' => 'Hanoi',
            'event_date' => now()->addWeek(),
            'capacity' => 100,
            'status' => 'published',
        ]);
    }

    private function registerAttendee(Event $event, User $attendee): void
    {
        Registration::create([
            'event_id' => $event->id,
            'attendee_id' => $attendee->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_guest_can_list_reviews_without_auth(): void
    {
        $organizer = User::factory()->create(['role' => 'organizer']);
        $attendee = User::factory()->create(['role' => 'attendee']);
        $event = $this->createEndedEvent($organizer);

        Review::create([
            'event_id' => $event->id,
            'attendee_id' => $attendee->id,
            'rating' => 5,
            'comment' => 'Great event!',
        ]);

        $response = $this->getJson("/api/events/{$event->id}/reviews");

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.rating', 5)
            ->assertJsonPath('data.0.comment', 'Great event!')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('meta.average_rating', 5);
    }

    public function test_attendee_can_submit_review_after_event_ended(): void
    {
        $organizer = User::factory()->create(['role' => 'organizer']);
        $attendee = User::factory()->create(['role' => 'attendee']);
        $event = $this->createEndedEvent($organizer);
        $this->registerAttendee($event, $attendee);

        Sanctum::actingAs($attendee);

        $response = $this->postJson("/api/events/{$event->id}/reviews", [
            'rating' => 4,
            'comment' => 'Very good experience',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Review submitted successfully.')
            ->assertJsonPath('data.rating', 4)
            ->assertJsonPath('data.comment', 'Very good experience');

        $this->assertDatabaseHas('reviews', [
            'event_id' => $event->id,
            'attendee_id' => $attendee->id,
            'rating' => 4,
            'comment' => 'Very good experience',
        ]);
    }

    public function test_cannot_review_before_event_ends(): void
    {
        $organizer = User::factory()->create(['role' => 'organizer']);
        $attendee = User::factory()->create(['role' => 'attendee']);
        $event = $this->createFutureEvent($organizer);
        $this->registerAttendee($event, $attendee);

        Sanctum::actingAs($attendee);

        $response = $this->postJson("/api/events/{$event->id}/reviews", [
            'rating' => 5,
            'comment' => 'Too early',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('message', 'You can only review after the event has ended.');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_cannot_submit_duplicate_review(): void
    {
        $organizer = User::factory()->create(['role' => 'organizer']);
        $attendee = User::factory()->create(['role' => 'attendee']);
        $event = $this->createEndedEvent($organizer);
        $this->registerAttendee($event, $attendee);

        Review::create([
            'event_id' => $event->id,
            'attendee_id' => $attendee->id,
            'rating' => 3,
            'comment' => 'First review',
        ]);

        Sanctum::actingAs($attendee);

        $response = $this->postJson("/api/events/{$event->id}/reviews", [
            'rating' => 5,
            'comment' => 'Second review attempt',
        ]);

        $response
            ->assertStatus(409)
            ->assertJsonPath('message', 'You have already reviewed this event.');

        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_cannot_review_without_registration(): void
    {
        $organizer = User::factory()->create(['role' => 'organizer']);
        $attendee = User::factory()->create(['role' => 'attendee']);
        $event = $this->createEndedEvent($organizer);

        Sanctum::actingAs($attendee);

        $response = $this->postJson("/api/events/{$event->id}/reviews", [
            'rating' => 5,
            'comment' => 'Not registered',
        ]);

        $response
            ->assertForbidden()
            ->assertJsonPath('message', 'Only attendees who joined this event can leave a review.');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_guest_cannot_submit_review(): void
    {
        $organizer = User::factory()->create(['role' => 'organizer']);
        $event = $this->createEndedEvent($organizer);

        $response = $this->postJson("/api/events/{$event->id}/reviews", [
            'rating' => 5,
            'comment' => 'Anonymous',
        ]);

        $response->assertUnauthorized();
    }
}
