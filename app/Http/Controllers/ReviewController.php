<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Registration;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * ES-87: Hiển thị danh sách review của event
     * GET /api/events/{event}/reviews
     */
    public function index(Event $event)
    {
        $reviews = Review::with('attendee:id,name')
            ->where('event_id', $event->id)
            ->latest()
            ->get();

        $averageRating = $reviews->count() > 0
            ? round($reviews->avg('rating'), 1)
            : 0;

        return response()->json([
            'data' => $reviews,
            'meta' => [
                'total' => $reviews->count(),
                'average_rating' => $averageRating,
            ],
        ]);
    }

    /**
     * ES-84 + ES-111 + ES-85: Tạo review
     * POST /api/events/{event}/reviews
     */
    public function store(Request $request, Event $event)
    {
        $user = $request->user();

        // ES-111: Chỉ review sau khi event kết thúc
        if ($event->event_date->isFuture()) {
            return response()->json([
                'message' => 'You can only review after the event has ended.',
            ], 422);
        }

        // Chỉ attendee đã đăng ký mới được review
        $hasJoined = Registration::where('event_id', $event->id)
            ->where('attendee_id', $user->id)
            ->whereIn('status', ['confirmed', 'pending'])
            ->exists();

        if (!$hasJoined) {
            return response()->json([
                'message' => 'Only attendees who joined this event can leave a review.',
            ], 403);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['required', 'string', 'max:300'],
        ]);

        // ES-85: 1 review / user / event
        $existing = Review::where('event_id', $event->id)
            ->where('attendee_id', $user->id)
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'You have already reviewed this event.',
            ], 409);
        }

        $review = Review::create([
            'event_id' => $event->id,
            'attendee_id' => $user->id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
        ]);

        $review->load('attendee:id,name');

        return response()->json([
            'message' => 'Review submitted successfully.',
            'data' => $review,
        ], 201);
    }
}
