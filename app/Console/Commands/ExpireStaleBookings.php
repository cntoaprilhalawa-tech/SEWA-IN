<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Illuminate\Console\Command;

class ExpireStaleBookings extends Command
{
    protected $signature = 'bookings:expire-stale';

    protected $description = 'Batalkan otomatis booking yang menggantung agar tanggalnya bebas kembali';

    /** Batas waktu (jam) pemilik merespons booking pending. */
    private const OWNER_RESPONSE_HOURS = 48;

    /** Batas waktu (jam) peminjam membayar setelah booking disetujui (non-tunai). */
    private const PAYMENT_HOURS = 48;

    public function handle(): int
    {
        $pending = 0;
        $unpaid = 0;

        Booking::with(['renter', 'owner'])
            ->where('status', 'pending')
            ->where('created_at', '<', now()->subHours(self::OWNER_RESPONSE_HOURS))
            ->get()
            ->each(function (Booking $b) use (&$pending) {
                $b->update([
                    'status' => 'rejected',
                    'cancelled_reason' => 'Kedaluwarsa: pemilik tidak merespons dalam ' . self::OWNER_RESPONSE_HOURS . ' jam.',
                ]);
                $b->renter->notifyApp('Booking kedaluwarsa', 'Booking ' . $b->booking_code . ' dibatalkan otomatis karena pemilik tidak merespons.', route('bookings.show', $b));
                $b->owner->notifyApp('Booking kedaluwarsa', 'Booking ' . $b->booking_code . ' dibatalkan otomatis karena tidak ditanggapi.', route('bookings.show', $b));
                $pending++;
            });

        Booking::with(['renter', 'owner'])
            ->where('status', 'approved')
            ->where('payment_method', '!=', 'cash')
            ->whereIn('payment_status', ['unpaid', 'rejected'])
            ->where('updated_at', '<', now()->subHours(self::PAYMENT_HOURS))
            ->get()
            ->each(function (Booking $b) use (&$unpaid) {
                $b->update([
                    'status' => 'cancelled',
                    'cancelled_reason' => 'Kedaluwarsa: bukti pembayaran tidak dikirim dalam ' . self::PAYMENT_HOURS . ' jam setelah disetujui.',
                ]);
                $b->renter->notifyApp('Booking dibatalkan', 'Booking ' . $b->booking_code . ' dibatalkan otomatis karena belum dibayar.', route('bookings.show', $b));
                $b->owner->notifyApp('Booking dibatalkan', 'Booking ' . $b->booking_code . ' dibatalkan otomatis karena belum dibayar.', route('bookings.show', $b));
                $unpaid++;
            });

        $this->info("Kedaluwarsa: {$pending} pending, {$unpaid} belum dibayar.");

        return self::SUCCESS;
    }
}
