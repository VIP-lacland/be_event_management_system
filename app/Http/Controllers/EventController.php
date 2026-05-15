<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    public function create(Request $request)
    {
        // Validate input
        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string',
            'category'     => 'required|in:Music,Sports,Food & Drink,Arts,Education,Community',
            'location'     => 'required|string|max:255',
            'event_date'   => 'required|date|after:now',
            'capacity'     => 'required|integer|min:1',
            'status'       => 'nullable|in:draft,published,cancelled',
        ]);
        
        // 2. Gán ID người tạo (Đảm bảo an toàn)
        // Nếu bạn chưa làm hệ thống Login, hãy dùng tạm $request->organizer_id
        $data['organizer_id'] = Auth::id() ?? $request->organizer_id;
        // Kiểm tra nếu vẫn không có organizer_id thì báo lỗi để tránh lỗi SQL khóa ngoại
        if (!$data['organizer_id']) {
            return response()->json(['message' => 'The organizer_id is required.'], 422);
        }

        // 3. Thực hiện lưu vào Database
        $event = Event::create($data);

        // Create event logic here (e.g., save to database)

        return response()->json([
            'message' => 'Event created successfully',
            'event' => $event,
        ], 201);
    }

}
