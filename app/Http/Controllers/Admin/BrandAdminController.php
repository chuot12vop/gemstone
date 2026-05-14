<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BrandAdminController extends Controller
{
    private const PUBLIC_STORAGE_PREFIX = '/storage/';

    public function index()
    {
        return view('admin.brands.index', [
            'title' => 'Brands',
            'breadcrumbs' => [
                ['label' => 'Brands'],
            ],
            'brands' => Brand::query()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.brands.form', [
            'title' => 'New brand',
            'breadcrumbs' => [
                ['label' => 'Brands', 'url' => route('admin.brands.index')],
                ['label' => 'New'],
            ],
            'brand' => null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, null);
        $imageUrl = $this->storeImage($request->file('image'), 'brands');
        if ($imageUrl !== null) {
            $data['image'] = $imageUrl;
        }

        Brand::query()->create($data);

        return redirect()->route('admin.brands.index')->with('success', 'Brand created.');
    }

    public function edit(Brand $brand)
    {
        return view('admin.brands.form', [
            'title' => 'Edit brand',
            'breadcrumbs' => [
                ['label' => 'Brands', 'url' => route('admin.brands.index')],
                ['label' => $brand->name],
            ],
            'brand' => $brand,
        ]);
    }

    public function update(Request $request, Brand $brand)
    {
        $data = $this->validated($request, $brand);
        $imageUrl = $this->storeImage($request->file('image'), 'brands');
        if ($imageUrl !== null) {
            $this->deletePublicPath($brand->image);
            $data['image'] = $imageUrl;
        }

        $brand->update($data);

        return redirect()->route('admin.brands.index')->with('success', 'Brand updated.');
    }

    public function destroy(Brand $brand)
    {
        if ($brand->products()->exists()) {
            return redirect()->route('admin.brands.index')->with('error', 'Cannot delete a brand that still has products. Reassign those products first.');
        }

        $this->deletePublicPath($brand->image);
        $brand->delete();

        return redirect()->route('admin.brands.index')->with('success', 'Brand deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Brand $brand): array
    {
        $slugRule = Rule::unique('brands', 'slug');
        if ($brand !== null) {
            $slugRule = $slugRule->ignore($brand->id);
        }

        $validated = $request->validate([
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'name' => 'required|string|max:160',
            'slug' => ['nullable', 'string', 'max:160', $slugRule],
            'sort_order' => 'nullable|integer',
        ]);

        $slug = trim((string) ($validated['slug'] ?? ''));
        if ($slug === '') {
            $slug = Str::slug($validated['name']);
        }

        return [
            'name' => $validated['name'],
            'slug' => $slug !== '' ? $slug : 'brand',
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
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
