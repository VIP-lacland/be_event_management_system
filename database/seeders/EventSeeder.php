<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        // 🔑 Tạm tắt FK check để truncate an toàn
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('events')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Lấy ID organizer duy nhất đã tạo trong UserSeeder
        $organizerId = DB::table('users')
            ->where('email', 'organizer@test.com')
            ->value('id');

        // Check an toàn: nếu chưa có user thì dừng, tránh lỗi null
        if (!$organizerId) {
            $this->command->error('❌ Không tìm thấy organizer@test.com. Hãy chạy UserSeeder trước!');
            return;
        }

        $events = [
            [
                'organizer_id' => $organizerId,
                'title' => 'Summer Music Festival 2026',
                'description' => 'Festival âm nhạc mùa hè.',
                'category' => 'Music',
                'location' => 'Central Park',
                'event_date' => now()->addDays(30)->setTime(18, 0),
                'capacity' => 100,
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'organizer_id' => $organizerId, // Dùng chung organizer này
                'title' => 'Food & Drink Expo',
                'description' => 'Triển lãm ẩm thực.',
                'category' => 'Food & Drink',
                'location' => 'Convention Center',
                'event_date' => now()->addDays(7)->setTime(10, 0),
                'capacity' => 200,
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('events')->insert($events);
        $this->command->info('✅ Seeded ' . count($events) . ' events successfully!');
    }
}