<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use App\Models\Registration;
use App\Notifications\RegistrationStatusNotification;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $query = Event::published()
            ->withCount(['registrations as confirmed_count' => function ($q) {
                $q->where('status', 'confirmed');
            }])
            ->withCount(['registrations as registered_count' => function ($q) {
                $q->whereIn('status', ['confirmed', 'pending']);
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

        $event->loadCount(['registrations as waitlist_count' => function ($q) {
            $q->where('status', 'waitlist');
        }]);

        // Thêm fill_rate tính cả waitlist nếu cần
        $event->fill_rate = $event->capacity > 0
            ? round((($event->confirmed_count + ($event->waitlist_count ?? 0)) / $event->capacity) * 100, 1)
            : 0;

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
                $q->whereIn('status', ['confirmed', 'pending']);
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
                $q->whereIn('status', ['confirmed', 'pending']);
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

        $currentStatus = $event->status;
        $newStatus = $validated['status'];

        // Enforce status transition rules
        $allowedTransitions = [
            'draft' => ['draft', 'published', 'cancelled'],
            'published' => ['published', 'cancelled'],
            'cancelled' => ['cancelled'],
        ];

        if (!in_array($newStatus, $allowedTransitions[$currentStatus])) {
            return response()->json(['message' => "Cannot change status from {$currentStatus} to {$newStatus}."], 403);
        }

        // Only draft events can be fully edited.
        // If it's not draft, we ONLY update the status.
        if ($currentStatus !== 'draft') {
            $event->update(['status' => $newStatus]);
        } else {
            $event->update($validated);
        }

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

        $currentStatus = $event->status;
        $newStatus = $validated['status'];

        $allowedTransitions = [
            'draft' => ['draft', 'published', 'cancelled'],
            'published' => ['published', 'cancelled'],
            'cancelled' => ['cancelled'],
        ];

        if (!in_array($newStatus, $allowedTransitions[$currentStatus])) {
            return response()->json(['message' => "Cannot change status from {$currentStatus} to {$newStatus}."], 403);
        }

        $event->update(['status' => $newStatus]);

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

        // Sử dụng ID của user đã đăng nhập
        $data['organizer_id'] = Auth::id();

        $event = Event::create($data);

        return response()->json([
            'message' => 'Event created successfully',
            'event' => $event,
        ], 201);
    }

    /**
     * Attendee: register for an event
     * POST /api/events/{id}/register
     */
   public function register(Request $request, int $id)
    {
        $event = Event::findOrFail($id);
        $userId = $request->user()->id;

    // Check if already registered (any status except cancelled)
        $existing = \App\Models\Registration::where('event_id', $id)
            ->where('attendee_id', $userId)
            ->where('status', '!=', 'cancelled')
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'You have already registered for this event.',
                'registration' => $existing
            ], 409);
    }

        $registeredCount = \App\Models\Registration::where('event_id', $id)
            ->whereIn('status', ['confirmed', 'pending'])
            ->count();

        $isFull = $event->capacity > 0 && $registeredCount >= $event->capacity;
    
        if ($isFull) {
            $nextPosition = \App\Models\Registration::where('event_id', $id)
                ->where('status', 'waitlist')
                ->max('position') ?? 0;
        $registration = \App\Models\Registration::create([
            'event_id' => $id,
            'attendee_id' => $userId,
            'status' => 'waitlist',
            'position' => $nextPosition + 1
        ]);
        
        $request->user()->notify(new RegistrationStatusNotification($event, 'waitlist', 'Event is full. You have been added to the waitlist.'));

        return response()->json([
            'message' => 'Event is full. You have been added to the waitlist.',
            'status' => 'waitlist',
            'waitlist_position' => $registration->position,
            'registration' => $registration
        ], 201);
    }

    $registrationStatus = $event->price > 0 ? 'confirmed' : 'pending';
    
    $registration = \App\Models\Registration::create([
        'event_id' => $id,
        'attendee_id' => $userId,
        'status' => $registrationStatus
    ]);

    $request->user()->notify(new RegistrationStatusNotification($event, $registrationStatus, "You have successfully registered for {$event->title}."));

    return response()->json([
        'message' => 'Registration successful',
        'status' => $registrationStatus,
        'registration' => $registration
    ], 201);
}

    /**
     * Attendee: list my registered tickets
     * GET /api/attendee/tickets
     */
    public function myTickets(Request $request)
    {
        $userId = $request->user()->id;

        $registrations = \App\Models\Registration::with('event')
            ->where('attendee_id', $userId)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $registrations]);
    }

    /**
     * Attendee: cancel a registration
     * DELETE /api/attendee/tickets/{eventId}
     */
    public function cancelTicket(Request $request, int $eventId)
{
    $userId = $request->user()->id;

    $registration = \App\Models\Registration::where('event_id', $eventId)
        ->where('attendee_id', $userId)
        ->first();

    if (!$registration) {
        return response()->json(['message' => 'Registration not found.'], 404);
    }

    if ($registration->status === 'cancelled') {
        return response()->json(['message' => 'Ticket is already cancelled.'], 400);
    }

    // 🔄 Dùng transaction để đảm bảo atomicity
    $wasConfirmed = $registration->status === 'confirmed';

    \Illuminate\Support\Facades\DB::transaction(function () use ($registration, $userId, $wasConfirmed) {
        $eventId = $registration->event_id;
        $wasWaitlist = $registration->status === 'waitlist';
        
        $registration->delete(); 

        if ($wasConfirmed) {
            $nextInLine = Registration::where('event_id', $eventId)
                ->where('status', 'waitlist')
                ->orderBy('position', 'asc')
                ->first();

            if ($nextInLine) {
                // Promote lên confirmed
                $nextInLine->update([
                    'status' => 'confirmed',
                    'position' => null
                ]);

                // Giảm position của tất cả người còn lại trong waitlist
                Registration::where('event_id', $eventId)
                    ->where('status', 'waitlist')
                    ->where('id', '!=', $nextInLine->id)
                    ->decrement('position');

                // Gửi thông báo cho người được đôn vé
                $event = Event::find($eventId);
                $promotedUser = App\Models\User::find($nextInLine->attendee_id);
                if ($promotedUser && $event) {
                    $promotedUser->notify(new RegistrationStatusNotification($event, 'confirmed', "Great news! A spot opened up and you have been promoted from the waitlist to confirmed for {$event->title}."));
                }
            }
        }
    });

    return response()->json([
        'message' => 'Registration cancelled successfully',
        'auto_promoted' => $wasConfirmed 
    ]);
}

    /**
     * Organizer: list registrations for a specific event
     * GET /api/organizer/events/{id}/registrations
     */
    public function registrations(Request $request, int $id)
{
    $event = Event::where('organizer_id', $request->user()->id)->findOrFail($id);

        $registrations = \App\Models\Registration::with('attendee:id,name,email')
            ->where('event_id', $event->id)
            ->where('status', '!=', 'cancelled') 
            ->orderByRaw("CASE 
                WHEN status = 'pending' THEN 1
                WHEN status = 'confirmed' THEN 2
                WHEN status = 'waitlist' THEN 3
                ELSE 4 END")
            ->orderBy('position', 'asc') // Waitlist sort by position
            ->orderBy('created_at', 'asc')
            ->get();

    return response()->json(['data' => $registrations]);
}

    /**
     * Organizer: Update registration status
     * PATCH /api/organizer/events/{eventId}/registrations/{registrationId}/status
     */
    public function updateRegistrationStatus(Request $request, int $eventId, int $registrationId)
    {
        $event = Event::where('organizer_id', $request->user()->id)->findOrFail($eventId);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['confirmed', 'rejected', 'cancelled', 'waitlist', 'pending'])],
        ]);

        $registration = \App\Models\Registration::where('event_id', $event->id)
            ->findOrFail($registrationId);

        if ($registration->status === 'waitlist' && $validated['status'] === 'confirmed') {
            return response()->json([
            'message' => 'Cannot manually promote from waitlist. Promotion happens automatically when someone cancels.'
        ], 403);
    }

        $registration->update(['status' => $validated['status']]);

        return response()->json([
            'message' => 'Registration status updated successfully',
            'registration' => $registration
        ]);
    }
}
