<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    /**
     * Antrean bukti transfer yang menunggu konfirmasi pemilik.
     */
    public function index()
    {
        $bookings = Booking::with(['item', 'renter'])
            ->where('owner_id', auth()->id())
            ->where('payment_status', 'waiting')
            ->latest()
            ->paginate(10);

        return view('payments.index', compact('bookings'));
    }

    /**
     * Detail bukti transfer (pemilik).
     */
    public function show(Booking $booking)
    {
        $this->authorizeOwner($booking);
        $booking->load(['item', 'renter']);

        return view('payments.show', compact('booking'));
    }

    public function accept(Booking $booking)
    {
        $this->authorizeOwner($booking);

        if ($booking->payment_status !== 'waiting') {
            return redirect()->route('payments.index')->with('error', 'Pembayaran ini tidak sedang menunggu konfirmasi.');
        }

        $booking->update(['payment_status' => 'paid', 'paid_at' => now()]);

        $booking->renter->notifyApp('Pembayaran diterima', 'Pembayaran booking ' . $booking->booking_code . ' sudah dikonfirmasi pemilik.', route('bookings.show', $booking));

        return redirect()->route('payments.index')->with('success', 'Pembayaran diterima.');
    }

    public function reject(Booking $booking)
    {
        $this->authorizeOwner($booking);

        if ($booking->payment_status !== 'waiting') {
            return redirect()->route('payments.index')->with('error', 'Pembayaran ini tidak sedang menunggu konfirmasi.');
        }

        $booking->update(['payment_status' => 'rejected']);

        $booking->renter->notifyApp('Bukti pembayaran ditolak', 'Bukti booking ' . $booking->booking_code . ' ditolak. Unggah ulang bukti yang jelas dan sesuai nominal.', route('bookings.show', $booking));

        return redirect()->route('payments.index')->with('success', 'Bukti ditolak. Penyewa diminta mengunggah ulang.');
    }

    /**
     * Penyewa mengunggah (atau mengunggah ulang) bukti transfer.
     */
    public function upload(Request $request, Booking $booking)
    {
        if (auth()->id() !== $booking->renter_id) {
            abort(403, 'Hanya penyewa yang dapat mengunggah bukti transfer.');
        }

        if (($booking->payment_method ?? 'transfer') === 'cash') {
            return back()->with('error', 'Pembayaran tunai dilakukan langsung saat barang diambil, tanpa bukti transfer.');
        }

        if (!in_array($booking->status, ['approved', 'active']) || !in_array($booking->payment_status, ['unpaid', 'rejected'])) {
            return back()->with('error', 'Bukti transfer tidak dapat diunggah untuk pemesanan ini.');
        }

        $request->validate([
            'payment_proof' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ], [
            'payment_proof.required' => 'Pilih foto bukti transfer terlebih dahulu.',
            'payment_proof.image' => 'Bukti transfer harus berupa gambar.',
            'payment_proof.max' => 'Ukuran bukti transfer maksimal 2MB.',
        ]);

        // Hapus bukti lama (bisa tersimpan di disk privat atau, untuk data lama, disk publik)
        if ($booking->payment_proof) {
            Storage::disk('local')->delete($booking->payment_proof);
            Storage::disk('public')->delete($booking->payment_proof);
        }

        // Bukti bayar disimpan di disk PRIVAT (tidak bisa dibuka lewat URL publik)
        $booking->update([
            'payment_proof' => $request->file('payment_proof')->store('payment_proofs', 'local'),
            'payment_status' => 'waiting',
        ]);

        $booking->owner->notifyApp('Bukti pembayaran masuk', auth()->user()->name . ' mengirim bukti pembayaran booking ' . $booking->booking_code . '.', route('payments.show', $booking));

        return back()->with('success', 'Bukti transfer terkirim. Menunggu konfirmasi pemilik.');
    }

    /**
     * Pemilik menandai pembayaran tunai sudah diterima saat barang diambil.
     */
    public function cash(Booking $booking)
    {
        $this->authorizeOwner($booking);

        if (($booking->payment_method ?? '') !== 'cash' || $booking->payment_status === 'paid' || !in_array($booking->status, ['approved', 'active'])) {
            return back()->with('error', 'Pembayaran tunai tidak dapat dikonfirmasi untuk pemesanan ini.');
        }

        $booking->update(['payment_status' => 'paid', 'paid_at' => now()]);

        $booking->renter->notifyApp('Pembayaran tunai diterima', 'Pembayaran tunai booking ' . $booking->booking_code . ' sudah dicatat pemilik.', route('bookings.show', $booking));

        return back()->with('success', 'Pembayaran tunai ditandai sudah diterima.');
    }

    /**
     * Tampilkan bukti bayar: hanya pemilik, penyewa, atau admin pada booking tersebut.
     */
    public function proof(Booking $booking)
    {
        $user = auth()->user();
        $allowed = $user->isAdmin() || $user->id === $booking->owner_id || $user->id === $booking->renter_id;
        abort_unless($allowed, 403, 'Anda tidak berhak melihat bukti pembayaran ini.');
        abort_unless($booking->payment_proof, 404);

        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($booking->payment_proof)) {
                return response()->file(Storage::disk($disk)->path($booking->payment_proof));
            }
        }

        abort(404);
    }

    private function authorizeOwner(Booking $booking): void
    {
        if (auth()->id() !== $booking->owner_id) {
            abort(403, 'Hanya pemilik barang yang dapat memeriksa pembayaran ini.');
        }
    }
}
