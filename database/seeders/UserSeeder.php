<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       // 🔑 Tạm tắt kiểm tra khóa ngoại (chỉ dùng cho môi trường dev)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('users')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $users = [
            [
                'name' => 'Organizer Test',
                'email' => 'organizer@test.com',
                'password' => Hash::make('password123'),
                'role' => 'organizer',
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Attendee Test',
                'email' => 'attendee@test.com',
                'password' => Hash::make('password123'),
                'role' => 'attendee',
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('users')->insert($users);
        $this->command->info('✅ Seeded users successfully!');
    }
}