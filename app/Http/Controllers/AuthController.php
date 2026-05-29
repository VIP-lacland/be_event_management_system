<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeUserMail;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Events\Registered;

    class AuthController extends Controller
    {
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:attendee,organizer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
            ]);

            // Gửi email chào mừng bằng Mailable class
            Mail::to($user->email)->send(new WelcomeUserMail($user));

            return response()->json([
                'success' => true,
                'message' => 'Registration successful! Welcome email has been sent.',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xác thực email
     * GET /api/auth/email/verify/{id}/{hash}
     */
    /**
 * Xác thực email và redirect về Frontend
 * GET /api/auth/email/verify/{id}/{hash}
 */
public function verifyEmail($id, $hash)
{
    $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');

    try {
        $user = User::findOrFail($id);

        // Kiểm tra hash có đúng không
        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            // Link không hợp lệ → redirect về frontend với status error
            return redirect($frontendUrl . '/verify-email/error?message=' . urlencode('Invalid verification link'));
        }

        // Kiểm tra email đã verify chưa
        if ($user->hasVerifiedEmail()) {
            // Đã verify rồi → redirect success
            return redirect($frontendUrl . '/verify-email/success?message=' . urlencode('Email already verified') . '&already_verified=1');
        }

        // Mark email as verified
        $user->markEmailAsVerified();

        // Verify thành công → redirect về frontend success page
        return redirect($frontendUrl . '/verify-email/success?message=' . urlencode('Email verified successfully! You can now login.'));

    } catch (\Exception $e) {
        // Lỗi server → redirect error
        return redirect($frontendUrl . '/verify-email/error?message=' . urlencode('Verification failed. Please try again.'));
    }
}

    /**
     * Gửi lại email xác thực
     * POST /api/auth/email/resend
     */
    public function resendVerification(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Email already verified'
            ], 400);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'success' => true,
            'message' => 'Verification email sent successfully!'
        ], 200);
    }

    /**
     * Đăng nhập user
     * POST /api/auth/login
     */
    public function login(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation errors',
            'errors' => $validator->errors()
        ], 422);
    }

    // Kiểm tra credentials
    if (!Auth::attempt($request->only('email', 'password'))) {
        return response()->json([
            'success' => false,
            'message' => 'The provided credentials are incorrect.',
        ], 401);
    }

    $user = Auth::user();

    // ✅ XÓA PHẦN CHECK EMAIL VERIFIED
    // if (!$user->hasVerifiedEmail()) { ... }

    // Tạo Sanctum token
    $token = $user->createToken('auth-token')->plainTextToken;

    return response()->json([
        'success' => true,
        'message' => 'Login successful',
        'token' => $token,
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ]
    ]);
}

    /**
     * Đăng xuất user
     * DELETE /api/auth/logout (cần auth:sanctum)
     */
    public function logout(Request $request)
    {
        // Xóa token hiện tại đang dùng
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Lấy thông tin user hiện tại
     * GET /api/auth/me (cần auth:sanctum)
     */
    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user()
        ]);
    }
}
