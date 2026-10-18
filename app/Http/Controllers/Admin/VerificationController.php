<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function index()
    {
        $pending = User::where('verification_status', 'pending')->latest('updated_at')->get();
        $recent = User::whereIn('verification_status', ['verified', 'rejected'])
            ->where('role', '!=', 'admin')
            ->latest('updated_at')->limit(20)->get();

        return view('admin.verifications', compact('pending', 'recent'));
    }

    public function approve(User $user)
    {
        if ($user->verification_status !== 'pending') {
            return back()->with('error', 'Pengajuan ini tidak sedang menunggu verifikasi.');
        }

        $user->forceFill([
            'verification_status' => 'verified',
            'verified_at' => now(),
            'verification_note' => null,
        ])->save();

        $user->notifyApp('Akun terverifikasi', 'KTM Anda disetujui. Sekarang Anda bisa bertransaksi.', route('items.index'));

        return back()->with('success', 'Akun ' . $user->name . ' diverifikasi.');
    }

    public function reject(Request $request, User $user)
    {
        $validated = $request->validate([
            'note' => ['required', 'string', 'max:200'],
        ], [
            'note.required' => 'Alasan penolakan wajib diisi.',
        ]);

        if ($user->verification_status !== 'pending') {
            return back()->with('error', 'Pengajuan ini tidak sedang menunggu verifikasi.');
        }

        $user->forceFill([
            'verification_status' => 'rejected',
            'verification_note' => $validated['note'],
        ])->save();

        $user->notifyApp('Verifikasi ditolak', 'Alasan: ' . $validated['note'] . '. Unggah ulang KTM yang jelas.', route('verification.ktm'));

        return back()->with('success', 'Verifikasi ' . $user->name . ' ditolak.');
    }
}
