<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    /**
     * ES-48: Update event (all fields)
     * PUT /api/organizer/events/{id}
     */
    public function update(Request $request, int $id)
    {
        // ES-50: Ownership check – chỉ organizer sở hữu event mới được sửa
        $event = Event::where('organizer_id', $request->user()->id)
                      ->findOrFail($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', Rule::in(Event::CATEGORIES)],
            'location' => ['required', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'capacity' => ['required', 'integer', 'min:1'],
            'status' => ['required', Rule::in(Event::STATUSES)],
        ]);

        $event->update($validated);

        return response()->json([
            'message' => 'Event updated successfully',
            'event' => $event->fresh(),
        ]);
    }

    /**
     * ES-49: Change status only (Draft/Published/Cancelled)
     * PATCH /api/organizer/events/{id}/status
     */
    public function updateStatus(Request $request, int $id)
    {
        // ES-50: Ownership check
        $event = Event::where('organizer_id', $request->user()->id)
                      ->findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', Rule::in(Event::STATUSES)],
        ]);

        $event->update(['status' => $validated['status']]);

        return response()->json([
            'message' => 'Event status updated successfully',
            'event' => $event->fresh(),
        ]);
    }

    public function create(Request $request)
    {

        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string',
            'category'     => 'required|in:Music,Sports,Food & Drink,Arts,Education,Community',
            'location'     => 'required|string|max:255',
            'event_date'   => 'required|date|after:now',
            'capacity'     => 'required|integer|min:1',
            'status'       => 'nullable|in:draft,published,cancelled',
        ]);

        // Nếu bạn chưa làm hệ thống Login, hãy dùng tạm $request->organizer_id
        $data['organizer_id'] = Auth::id() ?? $request->organizer_id;
        // Kiểm tra nếu vẫn không có organizer_id thì báo lỗi để tránh lỗi SQL khóa ngoại
        if (!$data['organizer_id']) {
            return response()->json(['message' => 'The organizer_id is required.'], 422);
        }

        $event = Event::create($data);

        return response()->json([
            'message' => 'Event created successfully',
            'event' => $event,
        ], 201);
    }
}
