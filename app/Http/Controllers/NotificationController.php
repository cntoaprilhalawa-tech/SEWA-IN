<?php

namespace App\Http\Controllers;

class NotificationController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $notifications = $user->notifications()->latest()->paginate(20);
        $unreadIds = $user->unreadNotifications()->pluck('id')->all();

        // Tandai sudah dibaca setelah daftar diambil, supaya yang baru tetap tampil tebal sekali.
        $user->unreadNotifications()->update(['read_at' => now()]);

        return view('notifications.index', compact('notifications', 'unreadIds'));
    }
}
