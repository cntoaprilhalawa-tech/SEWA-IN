<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Display the authenticated user's profile.
     */
    public function show()
    {
        $user = auth()->user();
        $user->loadCount(['reviewsReceived as reviews_count']);
        
        $items = $user->items()->with(['images', 'category'])->latest()->get();
        $reviews = Review::where('reviewee_id', $user->id)->where('is_hidden', false)->with(['reviewer', 'item'])->latest()->get();
        $avgRating = $user->average_rating;

        return view('profile.show', compact('user', 'items', 'reviews', 'avgRating'));
    }

    /**
     * Show the form for editing the authenticated user's profile.
     */
    public function edit()
    {
        $user = auth()->user();

        return view('profile.edit', compact('user'));
    }

    /**
     * Update the authenticated user's profile in storage.
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'bio' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'address' => ['nullable', 'string', 'max:500'],
            'bank_name' => ['nullable', 'string', 'max:50'],
            'account_number' => ['nullable', 'string', 'max:30'],
            'qris_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'name.max' => 'Nama lengkap maksimal 255 karakter.',
            'phone.max' => 'Nomor telepon maksimal 20 karakter.',
            'bio.max' => 'Bio maksimal 500 karakter.',
            'address.max' => 'Alamat maksimal 500 karakter.',
            'avatar.image' => 'File avatar harus berupa gambar.',
            'avatar.mimes' => 'Format avatar harus berupa jpeg, png, jpg, atau webp.',
            'avatar.max' => 'Ukuran avatar maksimal 2MB.',
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar'] = $avatarPath;
        }

        if ($request->hasFile('qris_image')) {
            if ($user->qris_image && Storage::disk('public')->exists($user->qris_image)) {
                Storage::disk('public')->delete($user->qris_image);
            }
            $validated['qris_image'] = $request->file('qris_image')->store('qris', 'public');
        }

        $user->update($validated);

        return redirect()->back()->with('success', 'Profil berhasil diperbarui!');
    }

    /**
     * Display a public profile of another user.
     */
    public function showUser($id)
    {
        $user = ($id instanceof User) ? $id : User::findOrFail($id);
        $user->loadCount(['reviewsReceived as reviews_count']);

        $items = $user->items()->where('is_available', true)->with(['images', 'category'])->latest()->get();
        $reviews = Review::where('reviewee_id', $user->id)->where('is_hidden', false)->with(['reviewer', 'item'])->latest()->get();
        $avgRating = $user->average_rating;

        return view('profile.show', compact('user', 'items', 'reviews', 'avgRating'));
    }
}
