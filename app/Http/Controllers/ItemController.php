<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use App\Models\ItemImage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ItemController extends Controller
{
    /**
     * Display a listing of items with search and filters.
     */
    public function index(Request $request)
    {
        // Katalog terbuka untuk tamu, peminjam, dan pemilik (pemilik juga bisa menyewa barang orang lain).
        // Hanya admin yang diarahkan ke halaman utamanya.
        if (auth()->check() && auth()->user()->isAdmin()) {
            return redirect()->route(auth()->user()->homeRoute());
        }

        $query = Item::with(['user', 'category', 'images']);

        // Check if viewing items by a specific owner/user
        if ($request->filled('owner_id')) {
            $query->where('user_id', $request->owner_id);
        } else {
            $query->where('is_available', true);
        }

        // Search by keyword (name, description)
        $searchTerm = $request->input('search', $request->input('q'));
        if (!empty($searchTerm)) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $searchTerm . '%');
            });
        }

        // Filter by category (slug or id)
        if ($request->filled('category')) {
            $categoryFilter = (array) $request->category;
            $query->whereHas('category', function ($q) use ($categoryFilter) {
                $q->whereIn('slug', $categoryFilter)
                  ->orWhereIn('id', $categoryFilter);
            });
        }

        // Filter by min price
        if ($request->filled('min_price')) {
            $query->where('price_per_day', '>=', $request->min_price);
        }

        // Filter by max price
        if ($request->filled('max_price')) {
            $query->where('price_per_day', '<=', $request->max_price);
        }

        // Filter ketersediaan tanggal: sembunyikan barang yang bentrok dengan rentang tanggal
        if ($request->filled('available_from') && $request->filled('available_to')
            && strtotime($request->available_from) && strtotime($request->available_to)
            && $request->available_to >= $request->available_from) {
            $from = $request->available_from;
            $to = $request->available_to;
            $query->whereDoesntHave('bookings', function ($q) use ($from, $to) {
                $q->blocking()
                  ->where('start_date', '<=', $to)
                  ->where('end_date', '>=', $from);
            });
        }

        // Filter by condition
        if ($request->filled('condition')) {
            $query->whereIn('condition', array_map('strtolower', (array) $request->condition));
        }

        // Sorting
        $sort = $request->get('sort', 'newest');
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('price_per_day', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price_per_day', 'desc');
                break;
            case 'rating':
                $query->withAvg('reviews', 'rating')->orderByDesc('reviews_avg_rating');
                break;
            case 'newest':
            default:
                $query->latest();
                break;
        }

        $items = $query->paginate(12)->withQueryString();
        $categories = Category::withCount(['items' => function ($q) {
            $q->where('is_available', true);
        }])->get();

        return view('items.index', compact('items', 'categories'));
    }

    /**
     * Show the form for creating a new item.
     */
    public function create()
    {
        $categories = Category::all();

        return view('items.create', compact('categories'));
    }

    /**
     * Store a newly created item in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'description' => ['required', 'string'],
            'price_per_day' => ['required', 'numeric', 'min:1'],
            'deposit' => ['nullable', 'numeric', 'min:0'],
            'condition' => ['required', 'in:baru,baik,cukup'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'address' => ['nullable', 'string'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ], [
            'name.required' => 'Nama barang wajib diisi.',
            'category_id.required' => 'Kategori wajib dipilih.',
            'category_id.exists' => 'Kategori yang dipilih tidak valid.',
            'description.required' => 'Deskripsi barang wajib diisi.',
            'price_per_day.required' => 'Harga sewa per hari wajib diisi.',
            'price_per_day.numeric' => 'Harga sewa harus berupa angka.',
            'price_per_day.min' => 'Harga sewa harus lebih dari Rp0.',
            'deposit.numeric' => 'Deposit harus berupa angka.',
            'deposit.min' => 'Deposit tidak boleh negatif.',
            'condition.required' => 'Kondisi barang wajib dipilih.',
            'condition.in' => 'Kondisi barang harus baru, baik, atau cukup.',
            'images.max' => 'Maksimal 5 foto barang.',
            'images.*.image' => 'File harus berupa gambar.',
            'images.*.mimes' => 'Format gambar harus jpeg, png, jpg, atau webp.',
            'images.*.max' => 'Ukuran setiap gambar maksimal 2MB.',
        ]);

        // Generate unique slug
        $baseSlug = Str::slug($validated['name']);
        if (empty($baseSlug)) {
            $baseSlug = 'item';
        }
        $slug = $baseSlug . '-' . Str::lower(Str::random(6));
        while (Item::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . Str::lower(Str::random(6));
        }

        $item = Item::create([
            'user_id' => auth()->id(),
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'],
            'price_per_day' => $validated['price_per_day'],
            'deposit' => $validated['deposit'] ?? 0,
            'condition' => $validated['condition'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'address' => $validated['address'] ?? null,
            'is_available' => true,
        ]);

        // Handle image uploads
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $imageFile) {
                $path = $imageFile->store('items', 'public');
                ItemImage::create([
                    'item_id' => $item->id,
                    'image_path' => $path,
                    'is_primary' => ($index === 0),
                ]);
            }
        }

        return redirect()->route('items.show', $item)->with('success', 'Barang berhasil ditambahkan!');
    }

    /**
     * Display the specified item.
     */
    public function show(Item $item)
    {
        $item->load([
            'user',
            'images',
            'category',
            'reviews.reviewer',
            'bookings' => function ($query) {
                $query->whereIn('status', \App\Models\Booking::BLOCKING_STATUSES)
                      ->where('end_date', '>=', now()->toDateString());
            }
        ]);

        // Get booked dates array for calendar display
        $bookedDates = [];
        $activeBookings = $item->bookings()
            ->whereIn('status', \App\Models\Booking::BLOCKING_STATUSES)
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

        return view('items.show', compact('item', 'bookedDates'));
    }

    /**
     * Show the form for editing the specified item.
     */
    public function edit(Item $item)
    {
        if (auth()->id() !== $item->user_id) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah barang ini.');
        }

        $categories = Category::all();

        return view('items.edit', compact('item', 'categories'));
    }

    /**
     * Update the specified item in storage.
     */
    public function update(Request $request, Item $item)
    {
        if (auth()->id() !== $item->user_id) {
            abort(403, 'Anda tidak memiliki akses untuk memperbarui barang ini.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'description' => ['required', 'string'],
            'price_per_day' => ['required', 'numeric', 'min:1'],
            'deposit' => ['nullable', 'numeric', 'min:0'],
            'condition' => ['required', 'in:baru,baik,cukup'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'address' => ['nullable', 'string'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'delete_images' => ['nullable', 'array'],
            'delete_images.*' => ['integer', 'exists:item_images,id'],
        ], [
            'name.required' => 'Nama barang wajib diisi.',
            'category_id.required' => 'Kategori wajib dipilih.',
            'category_id.exists' => 'Kategori yang dipilih tidak valid.',
            'description.required' => 'Deskripsi barang wajib diisi.',
            'price_per_day.min' => 'Harga sewa harus lebih dari Rp0.',
            'price_per_day.required' => 'Harga sewa per hari wajib diisi.',
            'condition.required' => 'Kondisi barang wajib dipilih.',
            'images.max' => 'Maksimal 5 foto barang.',
            'images.*.image' => 'File harus berupa gambar.',
            'images.*.max' => 'Ukuran setiap gambar maksimal 2MB.',
        ]);

        // Handle deleting existing images
        if ($request->filled('delete_images')) {
            $imagesToDelete = ItemImage::where('item_id', $item->id)
                ->whereIn('id', $request->delete_images)
                ->get();

            foreach ($imagesToDelete as $image) {
                if (Storage::disk('public')->exists($image->image_path)) {
                    Storage::disk('public')->delete($image->image_path);
                }
                $image->delete();
            }
        }

        // Handle new image uploads (ensure total images <= 5)
        if ($request->hasFile('images')) {
            $currentImagesCount = $item->images()->count();
            $availableSlots = max(0, 5 - $currentImagesCount);
            $newFiles = array_slice($request->file('images'), 0, $availableSlots);

            foreach ($newFiles as $imageFile) {
                $path = $imageFile->store('items', 'public');
                ItemImage::create([
                    'item_id' => $item->id,
                    'image_path' => $path,
                    'is_primary' => false,
                ]);
            }
        }

        // Ensure at least one image is primary if images exist
        if ($item->images()->count() > 0 && !$item->images()->where('is_primary', true)->exists()) {
            $item->images()->first()->update(['is_primary' => true]);
        }

        $item->update([
            'name' => $validated['name'],
            'category_id' => $validated['category_id'],
            'description' => $validated['description'],
            'price_per_day' => $validated['price_per_day'],
            'deposit' => $validated['deposit'] ?? 0,
            'condition' => $validated['condition'],
            'latitude' => $validated['latitude'] ?? $item->latitude,
            'longitude' => $validated['longitude'] ?? $item->longitude,
            'address' => $validated['address'] ?? $item->address,
        ]);

        return redirect()->back()->with('success', 'Barang berhasil diperbarui!');
    }

    /**
     * Remove the specified item from storage.
     */
    public function destroy(Item $item)
    {
        if (auth()->id() !== $item->user_id) {
            abort(403, 'Anda tidak memiliki akses untuk menghapus barang ini.');
        }

        // Jangan hapus barang yang masih punya booking berjalan
        $hasOpenBooking = $item->bookings()
            ->whereIn('status', \App\Models\Booking::BLOCKING_STATUSES)
            ->exists();

        if ($hasOpenBooking) {
            return redirect()->back()->with('error', 'Barang tidak dapat dihapus karena masih memiliki booking pending, disetujui, atau sedang berjalan.');
        }

        // Punya riwayat transaksi: nonaktifkan saja agar riwayat booking, pembayaran, dan review tidak hilang
        if ($item->bookings()->exists()) {
            $item->update(['is_available' => false]);

            return redirect()->route('dashboard')->with('success', 'Barang memiliki riwayat transaksi, jadi dinonaktifkan (tidak dihapus) supaya riwayat tetap utuh.');
        }

        // Delete image files from storage
        foreach ($item->images as $image) {
            if (Storage::disk('public')->exists($image->image_path)) {
                Storage::disk('public')->delete($image->image_path);
            }
        }

        // Delete item and cascades (images, bookings, reviews)
        $item->delete();

        return redirect()->route('dashboard')->with('success', 'Barang berhasil dihapus.');
    }

    /**
     * Toggle item availability.
     */
    public function toggleAvailability(Item $item)
    {
        if (auth()->id() !== $item->user_id) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah status ketersediaan barang ini.');
        }

        $item->update([
            'is_available' => !$item->is_available,
        ]);

        $statusText = $item->is_available ? 'tersedia' : 'tidak tersedia';

        return redirect()->back()->with('success', "Status barang berhasil diubah menjadi {$statusText}.");
    }
}
