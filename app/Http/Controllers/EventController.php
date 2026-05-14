<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $query = Event::query()->where('status', 'published');

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

        $events = $query->orderBy('event_date')->get();

        return response()->json(['data' => $events]);
    }

    public function show(Event $event)
    {
        abort_if($event->status !== 'published', 404);
        return response()->json(['data' => $event]);
    }
}
