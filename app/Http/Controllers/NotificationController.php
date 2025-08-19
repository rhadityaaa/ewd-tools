<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificationController extends Controller
{
    /**
     * Display a listing of notifications.
     */
    public function index()
    {
        $user = Auth::user();
        
        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return Inertia::render('notifications/Index', [
            'notifications' => $notifications
        ]);
    }

    /**
     * Get notifications for dropdown (latest 10)
     */
    public function getRecent()
    {
        $user = Auth::user();
        
        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $user->unreadNotifications()->count()
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead($id)
    {
        $user = Auth::user();
        
        $notification = $user->notifications()->find($id);
        
        if ($notification) {
            $notification->markAsRead();
        }
        
        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        
        $user->unreadNotifications->markAsRead();
        
        return response()->json(['success' => true]);
    }

    /**
     * Delete a notification.
     */
    public function destroy($id)
    {
        $user = Auth::user();
        
        $notification = $user->notifications()->find($id);
        
        if ($notification) {
            $notification->delete();
        }
        
        return response()->json(['success' => true]);
    }

    /**
     * Delete all notifications.
     */
    public function destroyAll()
    {
        $user = Auth::user();
        
        $user->notifications()->delete();
        
        return response()->json(['success' => true]);
    }
}