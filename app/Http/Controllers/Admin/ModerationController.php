<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Review;
use Illuminate\Http\Request;

class ModerationController extends Controller
{
    public function index()
    {
        $stat = [
            'new' => Complaint::where('status', 'new')->count(),
            'reviewing' => Complaint::where('status', 'reviewing')->count(),
            'resolved_this_month' => Complaint::where('status', 'resolved')
                ->where('updated_at', '>=', now()->startOfMonth())->count(),
        ];

        $complaints = Complaint::with(['reporter', 'reported', 'booking.item'])->latest()->limit(50)->get();
        $reviews = Review::with(['reviewer', 'item'])->latest()->limit(50)->get();

        return view('admin.moderation', compact('stat', 'complaints', 'reviews'));
    }

    public function updateComplaint(Request $request, Complaint $complaint)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:new,reviewing,resolved'],
            'admin_action' => ['nullable', 'in:none,warning,suspend,dismissed'],
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        $action = $validated['admin_action'] ?? null;
        $note = $validated['admin_note'] ?? null;

        if (in_array($action, ['warning', 'suspend'], true) && empty($note)) {
            return back()->with('error', 'Isi catatan admin sebagai alasan teguran/suspend.');
        }

        // Tindakan hanya dijalankan sekali per keluhan
        $alreadyActed = in_array($complaint->admin_action, ['warning', 'suspend'], true);
        if (!$alreadyActed && $action === 'warning') {
            $complaint->reported->applyWarning($note);
        } elseif (!$alreadyActed && $action === 'suspend') {
            $complaint->reported->suspendAccount($note);
        }

        $statusChanged = $complaint->status !== $validated['status'];

        $complaint->update([
            'status' => $validated['status'],
            'admin_action' => $alreadyActed ? $complaint->admin_action : $action,
            'admin_note' => $note ?? $complaint->admin_note,
        ]);

        if ($statusChanged) {
            $labels = ['new' => 'Baru', 'reviewing' => 'Sedang ditinjau', 'resolved' => 'Selesai'];
            $complaint->reporter->notifyApp('Status keluhan diperbarui', 'Keluhan Anda kini: ' . $labels[$validated['status']] . '.', route('bookings.index'));
        }

        return back()->with('success', 'Keluhan diperbarui.');
    }

    public function toggleReview(Review $review)
    {
        $review->update(['is_hidden' => !$review->is_hidden]);

        return back()->with('success', $review->is_hidden ? 'Ulasan disembunyikan.' : 'Ulasan ditampilkan kembali.');
    }
}
