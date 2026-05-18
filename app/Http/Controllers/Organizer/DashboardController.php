<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * GET /api/organizer/dashboard
     * Trả về toàn bộ data cho dashboard của organizer
     */
    public function dashboard(Request $request): JsonResponse
    {
        $organizerId = $request->user()->id;

        // ── Metric cards ──────────────────────────────────────────
        $totalEvents = Event::where('organizer_id', $organizerId)->count();

        $publishedEvents = Event::where('organizer_id', $organizerId)
            ->where('status', 'published')
            ->count();

        $totalAttendees = Registration::whereHas('event', function ($q) use ($organizerId) {
            $q->where('organizer_id', $organizerId);
        })->where('status', 'confirmed')->count();

        $waitlistCount = Registration::whereHas('event', function ($q) use ($organizerId) {
            $q->where('organizer_id', $organizerId);
        })->where('status', 'waitlist')->count();

        $upcomingEvents = Event::where('organizer_id', $organizerId)
            ->where('status', 'published')
            ->where('event_date', '>', now())
            ->count();

        $cancelledEvents = Event::where('organizer_id', $organizerId)
            ->where('status', 'cancelled')
            ->count();

        // ── Events by category (bar chart) ────────────────────────
        $eventsByCategory = Event::where('organizer_id', $organizerId)
            ->select('category', DB::raw('count(*) as total'))
            ->groupBy('category')
            ->get()
            ->map(fn($item) => [
                'category' => $item->category,
                'total'    => $item->total,
            ]);

        // ── Registrations per month (line chart) ──────────────────
        $registrationsPerMonth = Registration::whereHas('event', function ($q) use ($organizerId) {
            $q->where('organizer_id', $organizerId);
        })
            ->select(
                DB::raw('MONTH(registrations.created_at) as month'),
                DB::raw('YEAR(registrations.created_at) as year'),
                DB::raw('count(*) as total')
            )
            ->where('registrations.created_at', '>=', now()->subMonths(6))
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->map(fn($item) => [
                'month' => $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT),
                'total' => $item->total,
            ]);

        // ── Events by status (donut chart) ────────────────────────
        $eventsByStatus = Event::where('organizer_id', $organizerId)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->map(fn($item) => [
                'status' => $item->status,
                'total'  => $item->total,
            ]);

        // ── Recent events ─────────────────────────────────────────
        $recentEvents = Event::where('organizer_id', $organizerId)
            ->withCount([
                'registrations as confirmed_count' => fn($q) => $q->where('status', 'confirmed'),
                'registrations as waitlist_count'  => fn($q) => $q->where('status', 'waitlist'),
            ])
            ->orderByDesc('created_at')
            ->take(5)
            ->get()
            ->map(fn($event) => [
                'id'              => $event->id,
                'title'           => $event->title,
                'category'        => $event->category,
                'location'        => $event->location,
                'event_date'      => $event->event_date,
                'capacity'        => $event->capacity,
                'status'          => $event->status,
                'confirmed_count' => $event->confirmed_count,
                'waitlist_count'  => $event->waitlist_count,
                'fill_rate'       => $event->capacity > 0
                    ? round(($event->confirmed_count / $event->capacity) * 100)
                    : 0,
            ]);

        return response()->json([
            'metrics' => [
                'total_events'     => $totalEvents,
                'published_events' => $publishedEvents,
                'total_attendees'  => $totalAttendees,
                'waitlist_count'   => $waitlistCount,
                'upcoming_events'  => $upcomingEvents,
                'cancelled_events' => $cancelledEvents,
            ],
            'charts' => [
                'events_by_category'       => $eventsByCategory,
                'registrations_per_month'  => $registrationsPerMonth,
                'events_by_status'         => $eventsByStatus,
            ],
            'recent_events' => $recentEvents,
        ]);
    }
}