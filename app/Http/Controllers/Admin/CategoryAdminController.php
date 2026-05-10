<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryAdminController extends Controller
{
    private const PUBLIC_STORAGE_PREFIX = '/storage/';

    public function index()
    {
        return view('admin.categories.index', [
            'title' => 'Categories',
            'breadcrumbs' => [
                ['label' => 'Categories'],
            ],
            'categories' => Category::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.categories.form', [
            'title' => 'New category',
            'breadcrumbs' => [
                ['label' => 'Categories', 'url' => route('admin.categories.index')],
                ['label' => 'New'],
            ],
            'category' => null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $imageUrl = $this->storeImage($request->file('image'), 'categories');
        if ($imageUrl !== null) {
            $data['image'] = $imageUrl;
        }

        Category::query()->create($data);

        return redirect()->route('admin.categories.index')->with('success', 'Category created.');
    }

    public function edit(Category $category)
    {
        return view('admin.categories.form', [
            'title' => 'Edit category',
            'breadcrumbs' => [
                ['label' => 'Categories', 'url' => route('admin.categories.index')],
                ['label' => $category->name],
            ],
            'category' => $category,
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $data = $this->validated($request);
        $imageUrl = $this->storeImage($request->file('image'), 'categories');
        if ($imageUrl !== null) {
            $this->deletePublicPath($category->image);
            $data['image'] = $imageUrl;
        }

        $category->update($data);

        return redirect()->route('admin.categories.index')->with('success', 'Category updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'name' => 'required|string|max:160',
            'slug' => 'nullable|string|max:160',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'meta_title' => 'nullable|string|max:190',
            'meta_description' => 'nullable|string|max:320',
        ]);

        $slug = $validated['slug'] ?? '';
        if ($slug === '') {
            $slug = Str::slug($validated['name']);
        }

        return [
            'name' => $validated['name'],
            'slug' => $slug ?: 'category',
            'description' => $validated['description'] ?? null,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
        ];
    }

    private function storeImage(?UploadedFile $file, string $directory): ?string
    {
        if ($file === null) {
            return null;
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (! in_array($extension, $allowed, true)) {
            $extension = 'jpg';
        }

        $relativeDirectory = trim($directory, '/');
        $fileName = Str::uuid()->toString().'.'.$extension;
        $path = $file->storeAs($relativeDirectory, $fileName, 'public');

        if (! is_string($path) || $path === '') {
            return null;
        }

        return self::PUBLIC_STORAGE_PREFIX.$path;
    }

    private function deletePublicPath(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        $relativePath = Str::startsWith($path, self::PUBLIC_STORAGE_PREFIX)
            ? Str::after($path, self::PUBLIC_STORAGE_PREFIX)
            : ltrim($path, '/');

        if ($relativePath !== '') {
            Storage::disk('public')->delete($relativePath);
        }
    }
}
