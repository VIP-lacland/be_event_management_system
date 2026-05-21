<?php

namespace App\Http\Controllers\Attendee;

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
     * GET /api/attendee/dashboard
     * Trả về toàn bộ data cho dashboard của attendee
     */
    public function dashboard(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        // ── Metric cards ──────────────────────────────────────────
        $totalRegistrations = Registration::where('attendee_id', $userId)->count();

        $pendingRegistrations = Registration::where('attendee_id', $userId)
            ->where('status', 'pending')
            ->count();

        $confirmedRegistrations = Registration::where('attendee_id', $userId)
            ->where('status', 'confirmed')
            ->count();

        $waitlistRegistrations = Registration::where('attendee_id', $userId)
            ->where('status', 'waitlist')
            ->count();

        $cancelledRegistrations = Registration::where('attendee_id', $userId)
            ->whereIn('status', ['cancelled', 'rejected'])
            ->count();

        // ── Upcoming events ───────────────────────────────────────
        $upcomingEvents = Registration::where('attendee_id', $userId)
            ->where('status', 'confirmed')
            ->whereHas('event', function ($q) {
                $q->where('event_date', '>', now())
                  ->where('status', 'published');
            })
            ->count();

        // ── Registrations by category ─────────────────────────────
        $registrationsByCategory = Registration::where('attendee_id', $userId)
            ->where('status', 'confirmed')
            ->join('events', 'registrations.event_id', '=', 'events.id')
            ->select('events.category', DB::raw('count(*) as total'))
            ->groupBy('events.category')
            ->get()
            ->map(fn($item) => [
                'category' => $item->category,
                'total'    => $item->total,
            ]);

        // ── Registrations per month (line chart) ──────────────────
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $selectMonth = $isSqlite ? "strftime('%m', registrations.created_at)" : "MONTH(registrations.created_at)";
        $selectYear = $isSqlite ? "strftime('%Y', registrations.created_at)" : "YEAR(registrations.created_at)";

        $registrationsPerMonth = Registration::where('attendee_id', $userId)
            ->select(
                DB::raw("$selectMonth as month"),
                DB::raw("$selectYear as year"),
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

        // ── Recent registrations ──────────────────────────────────
        $recentRegistrations = Registration::where('attendee_id', $userId)
            ->with(['event:id,title,category,location,event_date,capacity,status'])
            ->orderByDesc('created_at')
            ->take(5)
            ->get()
            ->map(fn($reg) => [
                'id' => $reg->id,
                'event_id' => $reg->event_id,
                'event_title' => $reg->event->title,
                'event_category' => $reg->event->category,
                'event_location' => $reg->event->location,
                'event_date' => $reg->event->event_date,
                'status' => $reg->status,
                'created_at' => $reg->created_at,
            ]);

        // ── Pending registrations needing approval ─────────────────
        $pendingApprovalRegistrations = Registration::where('attendee_id', $userId)
            ->where('status', 'pending')
            ->with(['event:id,title,location,event_date'])
            ->orderByDesc('created_at')
            ->take(5)
            ->get()
            ->map(fn($reg) => [
                'id' => $reg->id,
                'event_id' => $reg->event_id,
                'event_title' => $reg->event->title,
                'event_location' => $reg->event->location,
                'event_date' => $reg->event->event_date,
                'created_at' => $reg->created_at,
            ]);

        return response()->json([
            'metrics' => [
                'total_registrations'    => $totalRegistrations,
                'pending_registrations'  => $pendingRegistrations,
                'confirmed_registrations' => $confirmedRegistrations,
                'waitlist_registrations' => $waitlistRegistrations,
                'cancelled_registrations' => $cancelledRegistrations,
                'upcoming_events'        => $upcomingEvents,
            ],
            'charts' => [
                'registrations_by_category' => $registrationsByCategory,
                'registrations_per_month'   => $registrationsPerMonth,
            ],
            'recent_registrations' => $recentRegistrations,
            'pending_approval_registrations' => $pendingApprovalRegistrations,
        ]);
    }
}
