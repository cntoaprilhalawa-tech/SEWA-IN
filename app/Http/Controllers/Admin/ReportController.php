<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'status' => ['nullable', 'in:pending,approved,active,completed,cancelled,rejected'],
        ]);

        $query = $this->filtered($request);

        $summary = [
            'count' => (clone $query)->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'revenue' => (clone $query)->where('status', 'completed')->sum('total_price'),
            'late_fee' => (clone $query)->where('status', 'completed')->sum('late_fee'),
            'deposit_deducted' => (clone $query)->where('status', 'completed')->sum('deposit_deduction'),
        ];

        $bookings = $query->with(['item', 'renter', 'owner'])->latest()->paginate(25)->withQueryString();

        return view('admin.reports', compact('bookings', 'summary'));
    }

    public function export(Request $request)
    {
        $rows = $this->filtered($request)->with(['item', 'renter', 'owner'])->latest()->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Kode', 'Barang', 'Peminjam', 'Pemilik', 'Mulai', 'Selesai', 'Status', 'Pembayaran', 'Total sewa', 'Deposit', 'Denda', 'Potongan deposit']);
            foreach ($rows as $b) {
                fputcsv($out, [
                    $b->booking_code,
                    $b->item->name ?? '-',
                    $b->renter->name ?? '-',
                    $b->owner->name ?? '-',
                    $b->start_date->format('Y-m-d'),
                    $b->end_date->format('Y-m-d'),
                    $b->status,
                    $b->payment_status,
                    (int) $b->total_price,
                    (int) $b->deposit,
                    (int) $b->late_fee,
                    (int) $b->deposit_deduction,
                ]);
            }
            fclose($out);
        }, 'laporan-transaksi-' . now()->format('Ymd-His') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filtered(Request $request)
    {
        $query = Booking::query();

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return $query;
    }
}
