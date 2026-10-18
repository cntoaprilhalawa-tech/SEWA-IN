<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\HumanCheck;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    /**
     * Show the application registration form.
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    /**
     * Handle a registration request for the application.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'nim' => ['required', 'string', 'min:8', 'unique:users,nim', 'regex:/^[0-9]+$/'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:peminjam,pemilik'],
            'ktm_image' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'human' => ['required', HumanCheck::rule()],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'name.max' => 'Nama maksimal 255 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'nim.required' => 'NIM wajib diisi.',
            'nim.unique' => 'NIM sudah terdaftar.',
            'nim.min' => 'NIM minimal 8 digit.',
            'nim.regex' => 'NIM harus berupa angka.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'role.required' => 'Pilih daftar sebagai peminjam atau pemilik barang.',
            'role.in' => 'Pilihan peran tidak valid.',
            'ktm_image.required' => 'Foto KTM wajib diunggah untuk verifikasi.',
            'ktm_image.image' => 'KTM harus berupa gambar.',
            'ktm_image.mimes' => 'Format KTM harus JPG, PNG, atau WEBP.',
            'ktm_image.max' => 'Ukuran foto KTM maksimal 2MB.',
            'human.required' => 'Centang verifikasi "saya bukan robot" terlebih dahulu.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'nim' => $validated['nim'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->forceFill([
            'role' => $validated['role'],
            'ktm_image' => $request->file('ktm_image')->store('ktm', 'local'),
            'verification_status' => 'pending',
        ])->save();

        foreach (User::where('role', 'admin')->get() as $admin) {
            $admin->notifyApp('Verifikasi KTM baru', $user->name . ' (NIM ' . $user->nim . ') mendaftar dan menunggu verifikasi.', route('admin.verifications'));
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('verification.ktm')
            ->with('success', 'Pendaftaran berhasil! KTM Anda akan diverifikasi admin sebelum Anda bisa bertransaksi.');
    }
}
