<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class KtmVerificationController extends Controller
{
    public function show()
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return redirect()->route($user->homeRoute());
        }

        return view('auth.ktm', compact('user'));
    }

    public function upload(Request $request)
    {
        $user = auth()->user();

        if ($user->isAdmin() || $user->isVerified()) {
            return redirect()->route($user->homeRoute());
        }

        if ($user->verification_status === 'pending') {
            return back()->with('error', 'KTM Anda sedang ditinjau admin.');
        }

        $request->validate([
            'ktm_image' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ], [
            'ktm_image.required' => 'Foto KTM wajib diunggah.',
            'ktm_image.image' => 'KTM harus berupa gambar.',
            'ktm_image.mimes' => 'Format KTM harus JPG, PNG, atau WEBP.',
            'ktm_image.max' => 'Ukuran foto KTM maksimal 2MB.',
        ]);

        if ($user->ktm_image) {
            Storage::disk('local')->delete($user->ktm_image);
        }

        $user->forceFill([
            'ktm_image' => $request->file('ktm_image')->store('ktm', 'local'),
            'verification_status' => 'pending',
            'verification_note' => null,
        ])->save();

        foreach (User::where('role', 'admin')->get() as $admin) {
            $admin->notifyApp('Verifikasi KTM baru', $user->name . ' (NIM ' . $user->nim . ') mengunggah KTM.', route('admin.verifications'));
        }

        return back()->with('success', 'KTM terkirim. Mohon tunggu verifikasi admin.');
    }

    /**
     * Foto KTM hanya boleh dilihat pemiliknya dan admin.
     */
    public function image(User $user)
    {
        $me = auth()->user();
        abort_unless($me->isAdmin() || $me->id === $user->id, 403, 'Anda tidak berhak melihat KTM ini.');
        abort_unless($user->ktm_image && Storage::disk('local')->exists($user->ktm_image), 404);

        return response()->file(Storage::disk('local')->path($user->ktm_image));
    }
}
