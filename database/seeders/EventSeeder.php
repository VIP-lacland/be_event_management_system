<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $organizerId = DB::table('users')
            ->where('email', 'organizer@test.com')
            ->value('id');

        if (!$organizerId) {
            $this->command->error('Organizer organizer@test.com was not found. Run UserSeeder first.');
            return;
        }

        DB::table('events')->where('organizer_id', $organizerId)->delete();

        $now = now();
        $events = [
            [
                'organizer_id' => $organizerId,
                'title' => 'Summer Music Festival 2026',
                'description' => 'Live bands, DJs, and an outdoor stage for a full summer night.',
                'category' => 'Music',
                'location' => 'Da Nang Convention Center',
                'event_date' => $now->copy()->addDays(18)->setTime(18, 30),
                'capacity' => 500,
                'status' => 'published',
            ],
            [
                'organizer_id' => $organizerId,
                'title' => 'Acoustic Night by the River',
                'description' => 'A relaxed acoustic show with local artists and riverside seating.',
                'category' => 'Music',
                'location' => 'Han River Park, Da Nang',
                'event_date' => $now->copy()->addDays(35)->setTime(19, 0),
                'capacity' => 180,
                'status' => 'published',
            ],
            [
                'organizer_id' => $organizerId,
                'title' => 'City Marathon 2026',
                'description' => 'Community running event with 5K, 10K, and half-marathon routes.',
                'category' => 'Sports',
                'location' => 'My Khe Beach, Da Nang',
                'event_date' => $now->copy()->addDays(12)->setTime(6, 0),
                'capacity' => 1200,
                'status' => 'published',
            ],
            [
                'organizer_id' => $organizerId,
                'title' => 'Weekend Football Cup',
                'description' => 'Amateur football tournament for company and university teams.',
                'category' => 'Sports',
                'location' => 'Hoa Xuan Stadium, Da Nang',
                'event_date' => $now->copy()->next('Saturday')->setTime(8, 0),
                'capacity' => 300,
                'status' => 'published',
            ],
            [
                'organizer_id' => $organizerId,
                'title' => 'Food & Drink Expo',
                'description' => 'A tasting event featuring street food, coffee, desserts, and craft drinks.',
                'category' => 'Food & Drink',
                'location' => 'Convention Center, Ho Chi Minh City',
                'event_date' => $now->copy()->addDays(9)->setTime(10, 0),
                'capacity' => 700,
                'status' => 'published',
            ],
            [
                'organizer_id' => $organizerId,
                'title' => 'Coffee Brewing Workshop',
                'description' => 'Hands-on workshop for pour-over, espresso, and cold brew techniques.',
                'category' => 'Food & Drink',
                'location' => 'District 1, Ho Chi Minh City',
                'event_date' => $now->copy()->addDays(24)->setTime(14, 0),
                'capacity' => 60,
                'status' => 'published',
            ],
            [
                'organizer_id' => $organizerId,
                'title' => 'Creative Arts Fair',
                'description' => 'Local artists exhibit paintings, handmade products, and digital works.',
                'category' => 'Arts',
                'location' => 'Da Nang Cultural Center',
                'event_date' => $now->copy()->addDays(21)->setTime(9, 30),
                'capacity' => 350,
                'status' => 'published',
            ],
            [
                'organizer_id' => $organizerId,
                'title' => 'Photography Walk',
                'description' => 'Guided city photo walk focused on street scenes and architecture.',
                'category' => 'Arts',
                'location' => 'Hoan Kiem Lake, Ha Noi',
                'event_date' => $now->copy()->addDays(16)->setTime(7, 30),
                'capacity' => 80,
                'status' => 'published',
            ],
            [
                'organizer_id' => $organizerId,
                'title' => 'Tech Innovators Summit',
                'description' => 'Talks and panels about AI, cloud platforms, and product development.',
                'category' => 'Education',
                'location' => 'Da Nang Innovation Hub',
                'event_date' => $now->copy()->addDays(27)->setTime(13, 0),
                'capacity' => 260,
                'status' => 'published',
            ],
            [
                'organizer_id' => $organizerId,
                'title' => 'Frontend Meetup',
                'description' => 'React, UI engineering, and design system sharing for developers.',
                'category' => 'Education',
                'location' => 'TechSpace, Ha Noi',
                'event_date' => $now->copy()->addDays(31)->setTime(18, 0),
                'capacity' => 120,
                'status' => 'published',
            ],
            [
                'organizer_id' => $organizerId,
                'title' => 'Volunteer Beach Cleanup',
                'description' => 'Community cleanup morning to help keep the beach clean and safe.',
                'category' => 'Community',
                'location' => 'My Khe Beach, Da Nang',
                'event_date' => $now->copy()->addDays(6)->setTime(8, 0),
                'capacity' => 220,
                'status' => 'published',
            ],
            [
                'organizer_id' => $organizerId,
                'title' => 'Neighborhood Charity Market',
                'description' => 'Small market raising funds for local community projects.',
                'category' => 'Community',
                'location' => 'Thu Duc City, Ho Chi Minh City',
                'event_date' => $now->copy()->addDays(40)->setTime(9, 0),
                'capacity' => 250,
                'status' => 'published',
            ],
        ];

        $events = array_map(fn ($event) => array_merge($event, [
            'created_at' => $now,
            'updated_at' => $now,
        ]), $events);

        DB::table('events')->insert($events);

        $this->command->info('Seeded ' . count($events) . ' published events successfully.');
    }
}
