<?php

namespace App\Http\Controllers;

use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;
use App\Models\User;

class GoogleAuthController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $user = User::where('google_id', $googleUser->id)
                ->orWhere('email', $googleUser->email)
                ->first();

            if (!$user) {
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'avatar' => $googleUser->avatar,
                    'role' => 'attendee', // Mặc định là attendee
                ]);
            } elseif (!$user->google_id) {
                $user->update([
                    'google_id' => $googleUser->id,
                    'avatar' => $googleUser->avatar,
                ]);
            }

            $token = $user->createToken('auth-token')->plainTextToken;

            // Trả token về cho Frontend qua URL. Chú ý port 5173 là port của React.
            return redirect('http://localhost:5173/login?token=' . $token . '&user=' . rawurlencode(json_encode($user)));

        } catch (\Exception $e) {
            return redirect('http://localhost:5173/login?error=google_auth_failed');
        }
    }
}
