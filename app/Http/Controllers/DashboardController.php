<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Item;
use App\Models\Review;
use App\Models\User;

class DashboardController extends Controller
{
    /**
     * Dashboard mengikuti mode aktif: peminjam dan pemilik masing-masing punya tampilan sendiri.
     */
    public function index()
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.moderation');
        }

        return $user->isPemilik() ? $this->pemilik($user) : $this->peminjam($user);
    }

    /**
     * Dashboard peminjam: semua angka dihitung dari booking milik user sebagai penyewa (renter_id).
     */
    private function peminjam(User $user)
    {
        $id = $user->id;
        $renting = fn () => Booking::where('renter_id', $id);

        // Perlu dibayar: sudah disetujui, belum lunas, dan bukan bayar tunai (tunai dibayar langsung ke pemilik)
        $needsPayment = fn () => $renting()
            ->whereIn('status', ['approved', 'active'])
            ->whereIn('payment_status', ['unpaid', 'rejected'])
            ->where(function ($q) {
                $q->whereNull('payment_method')->orWhere('payment_method', '!=', 'cash');
            });

        $needsReview = fn () => $renting()
            ->where('status', 'completed')
            ->whereDoesntHave('reviews', fn ($q) => $q->where('reviewer_id', $id));

        $stats = [
            'active' => $renting()->whereIn('status', ['approved', 'active'])->count(),
            'pending' => $renting()->where('status', 'pending')->count(),
            'to_pay' => $needsPayment()->count(),
            'completed' => $renting()->where('status', 'completed')->count(),
        ];

        $toPay = $needsPayment()->with('item')->latest()->limit(5)->get();
        $toReview = $needsReview()->with('item', 'owner')->latest()->limit(5)->get();
        $dueSoon = $renting()->where('status', 'active')
            ->whereDate('end_date', '<=', now()->addDays(2)->toDateString())
            ->with('item')->orderBy('end_date')->limit(5)->get();

        $currentBookings = $renting()->whereIn('status', ['pending', 'approved', 'active'])
            ->with(['item', 'owner'])->latest()->limit(6)->get();

        $latestItems = Item::with(['category', 'images'])
            ->where('is_available', true)
            ->where('user_id', '!=', $id)
            ->latest()->limit(4)->get();

        return view('dashboard.peminjam', compact(
            'user', 'stats', 'toPay', 'toReview', 'dueSoon', 'currentBookings', 'latestItems'
        ));
    }

    /**
     * Dashboard pemilik: semua angka dihitung dari booking atas barang milik user (owner_id).
     */
    private function pemilik(User $user)
    {
        $id = $user->id;
        $owned = fn () => Booking::where('owner_id', $id);

        $myItems = Item::where('user_id', $id)
            ->with(['images', 'category'])
            ->withCount('bookings')
            ->latest()
            ->get();

        $pendingRequests = $owned()->where('status', 'pending')
            ->with(['item', 'renter'])->latest()->get();

        $waitingPayments = $owned()->where('payment_status', 'waiting')
            ->with(['item', 'renter'])->latest()->limit(5)->get();

        $averageRating = Review::where('reviewee_id', $id)->where('is_hidden', false)->avg('rating');

        $stats = [
            'items' => $myItems->count(),
            'requests' => $pendingRequests->count(),
            'waiting_payments' => $owned()->where('payment_status', 'waiting')->count(),
            'active' => $owned()->whereIn('status', ['approved', 'active'])->count(),
            'completed' => $owned()->where('status', 'completed')->count(),
            'income' => (float) $owned()->where('payment_status', 'paid')
                ->whereNotIn('status', ['cancelled', 'rejected'])->sum('total_price'),
            'rating' => $averageRating !== null ? round((float) $averageRating, 1) : 0.0,
        ];

        return view('dashboard.pemilik', compact('user', 'stats', 'myItems', 'pendingRequests', 'waitingPayments'));
    }
}
