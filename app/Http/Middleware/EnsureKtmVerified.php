<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureKtmVerified
{
    /**
     * Fitur transaksi hanya untuk akun yang KTM/NIM-nya sudah diverifikasi admin.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && !$user->isAdmin() && !$user->isVerified()) {
            return redirect()->route('verification.ktm')
                ->with('error', 'Akun belum terverifikasi. Unggah KTM dan tunggu persetujuan admin untuk memakai fitur ini.');
        }

        return $next($request);
    }
}
