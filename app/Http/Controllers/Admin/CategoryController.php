<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('items')->orderBy('name')->get();

        return view('admin.categories', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:categories,name'],
            'icon' => ['nullable', 'string', 'max:50'],
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.unique' => 'Nama kategori sudah ada.',
        ]);

        Category::create([
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($validated['name']),
            'icon' => $validated['icon'] ?? null,
        ]);

        return back()->with('success', 'Kategori ditambahkan.');
    }

    public function update(Request $request, int $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:categories,name,' . $category->id],
            'icon' => ['nullable', 'string', 'max:50'],
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.unique' => 'Nama kategori sudah ada.',
        ]);

        $category->update([
            'name' => $validated['name'],
            'icon' => $validated['icon'] ?? null,
        ]);

        return back()->with('success', 'Kategori diperbarui.');
    }

    public function destroy(int $id)
    {
        $category = Category::withCount('items')->findOrFail($id);

        if ($category->items_count > 0) {
            return back()->with('error', 'Kategori masih dipakai ' . $category->items_count . ' barang, tidak dapat dihapus.');
        }

        $category->delete();

        return back()->with('success', 'Kategori dihapus.');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'kategori';
        $slug = $base;
        $i = 2;
        while (Category::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
