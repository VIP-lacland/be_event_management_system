<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Lấy danh sách thông báo của user hiện tại
     * GET /api/notifications
     */
    public function index(Request $request)
    {
        // Lấy 20 thông báo gần nhất, phân trang
        $notifications = $request->user()->notifications()->paginate(20);

        return response()->json([
            'unread_count' => $request->user()->unreadNotifications()->count(),
            'data' => $notifications
        ]);
    }

    /**
     * Đánh dấu 1 thông báo là đã đọc
     * PATCH /api/notifications/{id}/read
     */
    public function markAsRead(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read',
            'unread_count' => $request->user()->unreadNotifications()->count()
        ]);
    }

    /**
     * Đánh dấu tất cả thông báo là đã đọc
     * PATCH /api/notifications/read-all
     */
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'message' => 'All notifications marked as read',
            'unread_count' => 0
        ]);
    }
}
