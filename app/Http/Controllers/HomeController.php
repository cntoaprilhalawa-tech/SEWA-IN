<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Category;
use App\Models\Item;
use App\Models\User;

class HomeController extends Controller
{
    /**
     * Halaman depan. Tamu melihat landing page; yang sudah login
     * langsung diarahkan ke halaman utama sesuai modenya.
     */
    public function index()
    {
        if (auth()->check()) {
            return redirect()->route(auth()->user()->homeRoute());
        }

        $categories = Category::withCount(['items' => function ($q) {
            $q->where('is_available', true);
        }])->orderBy('name')->get();

        $latestItems = Item::with(['category', 'images'])
            ->where('is_available', true)
            ->latest()
            ->limit(8)
            ->get();

        $stats = [
            'items' => Item::where('is_available', true)->count(),
            'members' => User::where('role', '!=', 'admin')->where('verification_status', 'verified')->count(),
            'deals' => Booking::where('status', 'completed')->count(),
        ];

        return view('home', compact('categories', 'latestItems', 'stats'));
    }
}
