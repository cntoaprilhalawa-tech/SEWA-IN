<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RoleSwitchController extends Controller
{
    /**
     * Satu akun bisa menjadi peminjam sekaligus pemilik barang. Mode aktif disimpan
     * di database (users.active_mode) sehingga tetap sama saat login ulang.
     */
    public function switch(Request $request)
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return back()->with('error', 'Akun admin tidak dapat berpindah peran.');
        }

        $new = $user->isPemilik() ? 'peminjam' : 'pemilik';
        $user->forceFill(['active_mode' => $new])->save();

        // Boleh kembali ke halaman asal, tapi hanya path internal (cegah open redirect)
        $next = (string) $request->input('next', '');
        $target = (str_starts_with($next, '/') && !str_starts_with($next, '//') && !str_contains($next, '\\'))
            ? redirect($next)
            : redirect()->route('dashboard');

        return $target
            ->with('success', 'Sekarang kamu dalam mode ' . ($new === 'pemilik' ? 'Pemilik Barang' : 'Peminjam') . '.');
    }
}
