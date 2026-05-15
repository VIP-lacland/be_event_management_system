<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
}
