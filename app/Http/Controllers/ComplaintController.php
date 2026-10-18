<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Complaint;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ComplaintController extends Controller
{
    public function create()
    {
        $bookings = Booking::with(['item', 'renter', 'owner'])
            ->where(function ($q) {
                $q->where('renter_id', auth()->id())->orWhere('owner_id', auth()->id());
            })
            ->latest()
            ->get();

        return view('complaints.create', compact('bookings'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'booking_id' => ['required', 'exists:bookings,id'],
            'content' => ['required', 'string', 'min:10', 'max:1000'],
            'evidence' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ], [
            'booking_id.required' => 'Pilih booking terkait.',
            'content.required' => 'Isi keluhan wajib diisi.',
            'content.min' => 'Jelaskan keluhan minimal 10 karakter.',
            'content.max' => 'Isi keluhan maksimal 1000 karakter.',
            'evidence.image' => 'Bukti harus berupa gambar.',
            'evidence.max' => 'Ukuran bukti maksimal 2MB.',
        ]);

        $booking = Booking::findOrFail($validated['booking_id']);
        $me = auth()->id();

        if ($me !== $booking->renter_id && $me !== $booking->owner_id) {
            abort(403, 'Kamu tidak terlibat dalam booking ini.');
        }

        $complaint = Complaint::create([
            'booking_id' => $booking->id,
            // pihak yang dikeluhkan ditentukan server: lawan transaksi dari pelapor
            'reporter_id' => $me,
            'reported_id' => $me === $booking->renter_id ? $booking->owner_id : $booking->renter_id,
            'content' => $validated['content'],
            'status' => 'new',
            'evidence' => $request->hasFile('evidence') ? $request->file('evidence')->store('complaints', 'local') : null,
        ]);

        foreach (User::where('role', 'admin')->get() as $admin) {
            $admin->notifyApp('Keluhan baru', auth()->user()->name . ' mengajukan keluhan untuk booking ' . $booking->booking_code . '.', route('admin.moderation'));
        }

        return redirect()->route('bookings.index')->with('success', 'Keluhan terkirim. Admin akan meninjaunya.');
    }

    /**
     * Bukti keluhan: hanya pelapor, terlapor, dan admin.
     */
    public function evidence(Complaint $complaint)
    {
        $me = auth()->user();
        $allowed = $me->isAdmin() || $me->id === $complaint->reporter_id || $me->id === $complaint->reported_id;
        abort_unless($allowed, 403, 'Anda tidak berhak melihat bukti ini.');
        abort_unless($complaint->evidence && Storage::disk('local')->exists($complaint->evidence), 404);

        return response()->file(Storage::disk('local')->path($complaint->evidence));
    }
}
