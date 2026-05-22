<?php

namespace App\Models;

// ✅ 1. Import các class cần thiết
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // ← QUAN TRỌNG: Trait của Sanctum

class User extends Authenticatable
{
    // ✅ 2. Dùng trait HasApiTokens (phải có để dùng createToken())
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'google_id', // Thêm dòng này
        'avatar',    // Thêm dòng này
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
