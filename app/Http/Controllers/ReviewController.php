<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Store a newly created review in storage.
     */
    public function store(Request $request, Booking $booking)
    {
        $userId = auth()->id();

        // Peminjam menilai pemilik, pemilik menilai peminjam
        $isRenter = $userId === $booking->renter_id;
        $isOwner = $userId === $booking->owner_id;

        if (!$isRenter && !$isOwner) {
            abort(403, 'Hanya pihak yang terlibat dalam pemesanan ini yang dapat memberi ulasan.');
        }

        // Check booking status is completed
        if ($booking->status !== 'completed') {
            return back()->with('error', 'Ulasan hanya dapat diberikan setelah pemesanan berstatus selesai.');
        }

        // Check no existing review for this booking by this user
        $existingReview = Review::where('booking_id', $booking->id)
            ->where('reviewer_id', $userId)
            ->first();

        if ($existingReview) {
            return back()->with('error', 'Anda sudah memberikan ulasan untuk transaksi ini.');
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:500'],
        ], [
            'rating.required' => 'Rating bintang wajib dipilih.',
            'rating.integer' => 'Rating harus berupa angka bulat.',
            'rating.min' => 'Rating minimal 1 bintang.',
            'rating.max' => 'Rating maksimal 5 bintang.',
            'comment.max' => 'Komentar ulasan maksimal 500 karakter.',
        ]);

        Review::create([
            'booking_id' => $booking->id,
            'item_id' => $booking->item_id,
            'reviewer_id' => $userId,
            'reviewee_id' => $isRenter ? $booking->owner_id : $booking->renter_id,
            'direction' => $isRenter ? 'renter_to_owner' : 'owner_to_renter',
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
        ]);

        $target = $isRenter ? $booking->owner : $booking->renter;
        $target->notifyApp('Ulasan baru', auth()->user()->name . ' memberi Anda rating ' . $validated['rating'] . ' bintang.', route('user.profile', $target->id));

        return redirect()->back()->with('success', 'Terima kasih! Ulasan Anda berhasil dikirim.');
    }
}
