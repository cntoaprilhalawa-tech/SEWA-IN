<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureRole
{
    /**
     * Pakai: ->middleware('role:pemilik') atau 'role:peminjam,pemilik'.
     * Peran yang tidak cocok dikembalikan ke halaman utamanya sendiri.
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->isSuspended()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Akun Anda sedang disuspend.' . ($user->suspend_reason ? ' Alasan: ' . $user->suspend_reason : ''),
            ]);
        }

        $current = $user->isAdmin() ? 'admin' : ($user->isPemilik() ? 'pemilik' : 'peminjam');

        if (!in_array($current, $roles, true)) {
            return redirect()->route($user->homeRoute())
                ->with('error', 'Halaman tersebut bukan untuk akun '.$user->roleLabel().'.');
        }

        return $next($request);
    }
}
