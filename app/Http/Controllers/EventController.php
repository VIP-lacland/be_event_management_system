<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $query = Event::published()->upcoming()
            ->withCount(['registrations as confirmed_count' => function ($q) {
                $q->where('status', 'confirmed');
            }]);

        if ($search = $request->query('search')) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        if ($location = $request->query('location')) {
            $query->where('location', 'like', "%{$location}%");
        }

        if ($when = $request->query('when')) {
            $today = now();
            if ($when === 'today') {
                $query->whereDate('event_date', $today->toDateString());
            } elseif ($when === 'weekend') {
                $query->whereBetween('event_date', [
                    $today->startOfWeek()->addDays(5)->startOfDay(),
                    $today->endOfWeek()->endOfDay(),
                ]);
            }
        }

        $events = $query->orderBy('event_date')->paginate(12);

        return response()->json(['data' => $events]);
    }

    public function show(Event $event)
    {
        abort_if($event->status !== 'published', 404);
        
        $event->loadCount(['registrations as confirmed_count' => function ($q) {
            $q->where('status', 'confirmed');
        }]);

        return response()->json(['data' => $event]);
    }

    /**
     * Organizer: list my events (all statuses)
     * GET /api/organizer/events
     */
    public function myEvents(Request $request)
    {
        $events = Event::where('organizer_id', $request->user()->id)
            ->withCount(['registrations as confirmed_count' => function ($q) {
                $q->where('status', 'confirmed');
            }])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($event) {
                $event->fill_rate = $event->capacity > 0
                    ? round(($event->confirmed_count / $event->capacity) * 100, 1)
                    : 0;
                return $event;
            });

        return response()->json(['data' => $events]);
    }

    /**
     * Organizer: show one of my events
     * GET /api/organizer/events/{id}
     */
    public function myEventShow(Request $request, int $id)
    {
        $event = Event::where('organizer_id', $request->user()->id)
            ->withCount(['registrations as confirmed_count' => function ($q) {
                $q->where('status', 'confirmed');
            }])
            ->findOrFail($id);

        $event->fill_rate = $event->capacity > 0
            ? round(($event->confirmed_count / $event->capacity) * 100, 1)
            : 0;

        return response()->json(['event' => $event]);
    }
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
