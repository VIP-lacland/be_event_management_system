<?php
namespace App\Http\Controllers\Attendee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\User;
use App\Models\Registration;

class ProfileController extends Controller
{
    /**
     * GET /api/attendee/profile
     * Trả về thông tin user + stats
     */
    public function show(Request $request)
    {
        $user = $request->user();
        
        // Optional: thêm stats nếu cần
        $stats = [
            'total_registered' => Registration::where('attendee_id', $user->id)->count(),
            'total_confirmed' => Registration::where('attendee_id', $user->id)->where('status', 'confirmed')->count(),
        ];
        
        return response()->json([
            'user' => $user,
            'stats' => $stats
        ]);
    }

    /**
     * PUT /api/attendee/profile
     * Cập nhật Name + Email
     */
    public function update(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:6', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ]);
        
        $user->update($validated);
        
        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user->fresh()
        ]);
    }

    /**
     * GET /api/attendee/profile/tickets
     * Lấy danh sách đăng ký có phân trang + filter
     */
    public function tickets(Request $request)
    {
        $userId = $request->user()->id;
        $status = $request->query('status'); // pending|confirmed|cancelled|null
        $page = $request->query('page', 1);
        $perPage = 10;
        
        $query = Registration::with('event')
            ->where('attendee_id', $userId)
            ->where('status', '!=', 'rejected'); // Ẩn rejected khỏi danh sách
        
        if ($status && in_array($status, ['pending', 'confirmed', 'cancelled'])) {
            $query->where('status', $status);
        }
        
        $tickets = $query->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);
        
        return response()->json([
            'data' => $tickets->items(),
            'pagination' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'total' => $tickets->total(),
                'per_page' => $tickets->perPage(),
            ]
        ]);
    }

    /**
     * DELETE /api/attendee/profile/tickets/{eventId}
     * Hủy đăng ký (reuse logic từ EventController hoặc viết mới)
     */
    public function cancelTicket(Request $request, int $eventId)
    {
        $userId = $request->user()->id;

        $registration = Registration::where('event_id', $eventId)
            ->where('attendee_id', $userId)
            ->first();

        if (!$registration) {
            return response()->json(['message' => 'Registration not found'], 404);
        }

        if ($registration->status === 'cancelled') {
            return response()->json(['message' => 'Ticket is already cancelled'], 400);
        }

        // 🔄 Auto-promote logic (giữ nguyên như EventController)
        \Illuminate\Support\Facades\DB::transaction(function () use ($registration) {
            $eventId = $registration->event_id;
            $wasConfirmed = $registration->status === 'confirmed';

            $registration->delete(); // Hoặc update status='cancelled' nếu cần giữ history

            if ($wasConfirmed) {
                $nextInLine = Registration::where('event_id', $eventId)
                    ->where('status', 'waitlist')
                    ->orderBy('position', 'asc')
                    ->first();

                if ($nextInLine) {
                    $nextInLine->update(['status' => 'confirmed', 'position' => null]);
                    Registration::where('event_id', $eventId)
                        ->where('status', 'waitlist')
                        ->where('id', '!=', $nextInLine->id)
                        ->decrement('position');
                }
            }
        });

        // ✅ Trả về full registration object đã update
        return response()->json([
            'message' => 'Registration cancelled successfully',
            'registration' => $registration // Đã deleted, frontend sẽ filter ra
        ]);
    }
}