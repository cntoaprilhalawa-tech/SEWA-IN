<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Item;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{
    /**
     * Display a listing of bookings for the current user.
     */
    public function index(Request $request)
    {
        $role = auth()->user()->isPemilik() ? 'owner' : 'renter';
        $status = $request->get('status', 'all');

        $query = Booking::with(['item.images', 'renter', 'owner']);

        if ($role === 'owner') {
            $query->where('owner_id', auth()->id());
        } else {
            $query->where('renter_id', auth()->id());
        }

        if ($status !== 'all' && in_array($status, ['pending', 'approved', 'active', 'completed', 'cancelled', 'rejected'])) {
            $query->where('status', $status);
        }

        $bookings = $query->latest()->paginate(10)->withQueryString();

        return view('bookings.index', compact('bookings', 'role', 'status'));
    }

    /**
     * Show the booking form for an item.
     */
    public function create(Item $item)
    {
        if (!$item->is_available) {
            return redirect()->route('items.show', $item)
                ->with('error', 'Barang ini sedang tidak tersedia untuk disewa.');
        }

        if ($item->user_id === auth()->id()) {
            return redirect()->route('items.show', $item)
                ->with('error', 'Anda tidak dapat menyewa barang milik Anda sendiri.');
        }

        // Get booked dates array for JS calendar
        $bookedDates = [];
        $activeBookings = Booking::where('item_id', $item->id)
            ->blocking()
            ->where('end_date', '>=', now()->toDateString())
            ->get(['start_date', 'end_date']);

        foreach ($activeBookings as $booking) {
            $start = Carbon::parse($booking->start_date);
            $end = Carbon::parse($booking->end_date);
            while ($start->lte($end)) {
                $bookedDates[] = $start->format('Y-m-d');
                $start->addDay();
            }
        }
        $bookedDates = array_values(array_unique($bookedDates));

        return view('bookings.create', compact('item', 'bookedDates'));
    }

    /**
     * Store a newly created booking in storage.
     * Bukti pembayaran TIDAK diterima di sini: peminjam membayar setelah pemilik menyetujui.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_id' => ['required', 'exists:items,id'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'note' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['required', 'in:cash,transfer,qris'],
        ], [
            'item_id.required' => 'Barang harus dipilih.',
            'item_id.exists' => 'Barang tidak ditemukan.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'start_date.after_or_equal' => 'Tanggal mulai harus hari ini atau tanggal setelahnya.',
            'end_date.required' => 'Tanggal selesai wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai sewa.',
            'note.max' => 'Catatan maksimal 500 karakter.',
            'payment_method.required' => 'Pilih metode pembayaran.',
            'payment_method.in' => 'Metode pembayaran tidak valid.',
        ]);

        $item = Item::findOrFail($validated['item_id']);

        if (!$item->is_available) {
            return back()->withInput()->withErrors([
                'item_id' => 'Barang ini sedang tidak tersedia untuk disewa.',
            ]);
        }

        if ($item->user_id === auth()->id()) {
            return back()->withInput()->withErrors([
                'item_id' => 'Anda tidak dapat menyewa barang milik Anda sendiri.',
            ]);
        }

        // Calculate days and price
        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);
        $totalDays = $start->diffInDays($end) + 1;
        $totalPrice = $item->price_per_day * $totalDays;

        $method = $validated['payment_method'];
        $owner = $item->user;

        if ($method === 'transfer' && !($owner->bank_name && $owner->account_number)) {
            return back()->withInput()->withErrors(['payment_method' => 'Pemilik belum mengatur rekening bank. Pilih metode lain.']);
        }
        if ($method === 'qris' && !$owner->qris_image) {
            return back()->withInput()->withErrors(['payment_method' => 'Pemilik belum mengunggah kode QRIS. Pilih metode lain.']);
        }

        $deposit = $item->deposit ?? 0;

        // Cek bentrok + simpan dalam SATU transaksi dengan lock pada baris barang,
        // sehingga dua pengajuan bersamaan / klik ganda tidak bisa sama-sama lolos.
        $booking = DB::transaction(function () use ($item, $validated, $totalDays, $totalPrice, $method, $deposit) {
            Item::whereKey($item->id)->lockForUpdate()->first();

            // pending, approved, dan active memblokir tanggal
            $hasOverlap = Booking::where('item_id', $item->id)
                ->blocking()
                ->where('start_date', '<=', $validated['end_date'])
                ->where('end_date', '>=', $validated['start_date'])
                ->exists();

            if ($hasOverlap) {
                return null;
            }

            return Booking::create([
                'booking_code' => Booking::generateBookingCode(),
                'item_id' => $item->id,
                'renter_id' => auth()->id(),
                'owner_id' => $item->user_id,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'total_days' => $totalDays,
                'price_per_day' => $item->price_per_day,
                'total_price' => $totalPrice,
                'deposit' => $deposit,
                'deposit_status' => $deposit > 0 ? 'held' : 'none',
                'status' => 'pending',
                'note' => $validated['note'] ?? null,
                'payment_method' => $method,
                'payment_proof' => null,
                'payment_status' => 'unpaid',
            ]);
        });

        if (!$booking) {
            return back()->withInput()->withErrors([
                'start_date' => 'Barang tidak tersedia pada rentang tanggal tersebut karena sudah dibooking.',
            ]);
        }

        $owner->notifyApp(
            'Pengajuan sewa baru',
            auth()->user()->name . ' mengajukan sewa "' . $item->name . '" (' . $booking->booking_code . ').',
            route('bookings.show', $booking)
        );

        return redirect()->route('bookings.show', $booking)
            ->with('success', 'Pengajuan sewa berhasil dibuat! Menunggu konfirmasi pemilik barang. Pembayaran dilakukan setelah pemilik menyetujui.');
    }

    /**
     * Display the specified booking.
     */
    public function show(Booking $booking)
    {
        $userId = auth()->id();
        if ($userId !== $booking->renter_id && $userId !== $booking->owner_id) {
            abort(403, 'Anda tidak memiliki akses ke data pemesanan ini.');
        }

        $booking->load(['item.images', 'renter', 'owner', 'review.reviewer', 'reviews.reviewer']);

        return view('bookings.show', compact('booking'));
    }

    /**
     * Owner approves pending booking.
     */
    public function approve(Booking $booking)
    {
        if (auth()->id() !== $booking->owner_id) {
            abort(403, 'Hanya pemilik barang yang dapat menyetujui pemesanan ini.');
        }

        if ($booking->status !== 'pending') {
            return back()->with('error', 'Hanya pemesanan dengan status pending yang dapat disetujui.');
        }

        $booking->update(['status' => 'approved']);

        $booking->renter->notifyApp(
            'Booking disetujui',
            'Booking ' . $booking->booking_code . ' disetujui pemilik. Silakan lakukan pembayaran.',
            route('bookings.show', $booking)
        );

        return redirect()->back()->with('success', 'Pemesanan berhasil disetujui!');
    }

    /**
     * Owner rejects pending booking.
     */
    public function reject(Request $request, Booking $booking)
    {
        if (auth()->id() !== $booking->owner_id) {
            abort(403, 'Hanya pemilik barang yang dapat menolak pemesanan ini.');
        }

        if ($booking->status !== 'pending') {
            return back()->with('error', 'Hanya pemesanan dengan status pending yang dapat ditolak.');
        }

        $booking->update([
            'status' => 'rejected',
            'cancelled_reason' => $request->input('cancelled_reason') ?? $request->input('reason') ?? 'Ditolak oleh pemilik.',
        ]);

        $booking->renter->notifyApp(
            'Booking ditolak',
            'Booking ' . $booking->booking_code . ' ditolak pemilik. Alasan: ' . $booking->cancelled_reason,
            route('bookings.show', $booking)
        );

        return redirect()->back()->with('success', 'Pemesanan telah ditolak.');
    }

    /**
     * Renter cancels pending or approved booking.
     */
    public function cancel(Request $request, Booking $booking)
    {
        if (auth()->id() !== $booking->renter_id) {
            abort(403, 'Hanya penyewa yang dapat membatalkan pemesanan ini.');
        }

        if (!in_array($booking->status, ['pending', 'approved'])) {
            return back()->with('error', 'Pemesanan yang sedang aktif atau sudah selesai tidak dapat dibatalkan.');
        }

        if ($booking->payment_status === 'paid') {
            return back()->with('error', 'Pemesanan yang sudah dibayar tidak dapat dibatalkan. Hubungi pemilik atau ajukan keluhan.');
        }

        $booking->update([
            'status' => 'cancelled',
            'cancelled_reason' => $request->input('cancelled_reason') ?? $request->input('reason') ?? 'Dibatalkan oleh penyewa.',
        ]);

        $booking->owner->notifyApp(
            'Booking dibatalkan',
            auth()->user()->name . ' membatalkan booking ' . $booking->booking_code . '.',
            route('bookings.show', $booking)
        );

        return redirect()->back()->with('success', 'Pemesanan berhasil dibatalkan.');
    }

    /**
     * Owner marks booking as active (item handed over).
     */
    public function activate(Booking $booking)
    {
        if (auth()->id() !== $booking->owner_id) {
            abort(403, 'Hanya pemilik barang yang dapat mengaktifkan pemesanan ini.');
        }

        if ($booking->status !== 'approved') {
            return back()->with('error', 'Hanya pemesanan yang telah disetujui yang dapat diaktifkan.');
        }

        $booking->update(['status' => 'active']);

        $booking->renter->notifyApp(
            'Barang diserahkan',
            'Booking ' . $booking->booking_code . ' kini aktif. Kembalikan barang paling lambat ' . $booking->end_date->format('d M Y') . '.',
            route('bookings.show', $booking)
        );

        return redirect()->back()->with('success', 'Barang telah diserahkan. Status pemesanan kini aktif!');
    }

    /**
     * Owner confirms the item was returned: record actual return date,
     * calculate late fee, and settle the deposit.
     */
    public function complete(Request $request, Booking $booking)
    {
        if (auth()->id() !== $booking->owner_id) {
            abort(403, 'Hanya pemilik barang yang dapat menyelesaikan pemesanan ini.');
        }

        if ($booking->status !== 'active') {
            return back()->with('error', 'Hanya pemesanan yang sedang aktif yang dapat diselesaikan.');
        }

        $validated = $request->validate([
            'returned_at' => ['required', 'date', 'after_or_equal:' . $booking->start_date->toDateString(), 'before_or_equal:today'],
            'damage_note' => ['nullable', 'string', 'max:500'],
            'deposit_deduction' => ['nullable', 'numeric', 'min:0', 'max:' . (float) $booking->deposit],
        ], [
            'returned_at.required' => 'Tanggal pengembalian wajib diisi.',
            'returned_at.after_or_equal' => 'Tanggal pengembalian tidak boleh sebelum tanggal mulai sewa.',
            'returned_at.before_or_equal' => 'Tanggal pengembalian tidak boleh di masa depan.',
            'deposit_deduction.max' => 'Potongan deposit tidak boleh melebihi nominal deposit.',
            'deposit_deduction.min' => 'Potongan deposit tidak boleh negatif.',
        ]);

        $deduction = (float) ($validated['deposit_deduction'] ?? 0);
        if ($deduction > 0 && empty($validated['damage_note'])) {
            return back()->withInput()->withErrors([
                'damage_note' => 'Jelaskan kerusakan jika deposit dipotong.',
            ]);
        }

        // Denda: hari melewati tanggal selesai x tarif harian.
        $dueDate = Carbon::parse($booking->end_date)->startOfDay();
        $returnedAt = Carbon::parse($validated['returned_at'])->startOfDay();
        $lateDays = $returnedAt->gt($dueDate) ? $dueDate->diffInDays($returnedAt) : 0;
        $lateFee = $lateDays * Booking::LATE_FEE_PER_DAY;

        if ((float) $booking->deposit <= 0) {
            $depositStatus = 'none';
            $deduction = 0;
        } else {
            $depositStatus = $deduction > 0 ? 'deducted' : 'returned';
        }

        $booking->update([
            'status' => 'completed',
            'returned_at' => $returnedAt->toDateString(),
            'late_days' => $lateDays,
            'late_fee' => $lateFee,
            'damage_note' => $validated['damage_note'] ?? null,
            'deposit_deduction' => $deduction,
            'deposit_status' => $depositStatus,
        ]);

        $booking->renter->notifyApp(
            'Pemesanan selesai',
            'Booking ' . $booking->booking_code . ' selesai.' . ($lateDays > 0 ? ' Denda keterlambatan Rp' . number_format($lateFee, 0, ',', '.') . '.' : '') . ' Silakan beri ulasan.',
            route('bookings.show', $booking)
        );

        $msg = 'Barang telah dikembalikan. Pemesanan selesai!';
        if ($lateDays > 0) {
            $msg .= ' Terlambat ' . $lateDays . ' hari, denda Rp' . number_format($lateFee, 0, ',', '.') . '.';
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * API endpoint to check date availability.
     */
    public function checkAvailability(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id' => ['required', 'exists:items,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'available' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $item = Item::find($request->item_id);
        if (!$item || !$item->is_available) {
            return response()->json([
                'available' => false,
                'message' => 'Barang tidak tersedia untuk disewa.',
                'conflicts' => [],
            ]);
        }

        $conflicts = Booking::where('item_id', $item->id)
            ->blocking()
            ->where(function ($query) use ($request) {
                $query->where('start_date', '<=', $request->end_date)
                      ->where('end_date', '>=', $request->start_date);
            })
            ->get(['id', 'booking_code', 'start_date', 'end_date', 'status']);

        $available = $conflicts->isEmpty();

        $start = Carbon::parse($request->start_date);
        $end = Carbon::parse($request->end_date);
        $totalDays = $start->diffInDays($end) + 1;
        $totalPrice = $item->price_per_day * $totalDays;

        return response()->json([
            'available' => $available,
            'total_days' => $totalDays,
            'total_price' => $totalPrice,
            'deposit' => $item->deposit ?? 0,
            'conflicts' => $conflicts,
        ]);
    }
}
