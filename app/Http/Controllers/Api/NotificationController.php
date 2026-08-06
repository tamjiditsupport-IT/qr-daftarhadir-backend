<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $user->unreadNotifications->count(),
                'notifications' => $user->notifications()->take(50)->get()
            ]
        ]);
    }

    public function markAsRead($id)
    {
        $notification = auth()->user()->notifications()->find($id);
        
        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json(['success' => true]);
    }

    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        return response()->json(['success' => true]);
    }
}
