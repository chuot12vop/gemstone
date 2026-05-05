<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductAdminController extends Controller
{
    private const PUBLIC_STORAGE_PREFIX = '/storage/';

    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $query = Product::query()->with('category')->latest();
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', '%'.$q.'%')
                    ->orWhere('slug', 'like', '%'.$q.'%');
            });
        }

        return view('admin.products.index', [
            'title' => 'Products',
            'breadcrumbs' => [
                ['label' => 'Products'],
            ],
            'products' => $query->get(),
            'q' => $q,
        ]);
    }

    public function create()
    {
        return view('admin.products.form', [
            'title' => 'New product',
            'breadcrumbs' => [
                ['label' => 'Products', 'url' => route('admin.products.index')],
                ['label' => 'New'],
            ],
            'product' => null,
            'categories' => Category::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $attributes = $this->extractAttributes($request);
        $thumbnailUrl = $this->storeImage($request->file('thumbnail'), 'products/thumbnails');
        $galleryImageUrls = $this->storeMultipleImages($this->normalizeUploadedFiles($request->file('images')), 'products/gallery');
        if ($thumbnailUrl !== null) {
            $data['thumbnail'] = $thumbnailUrl;
            $data['image'] = $thumbnailUrl;
        }

        DB::transaction(function () use ($data, $attributes, $galleryImageUrls): void {
            $createPayload = $data;
            // Use a temporary unique slug to avoid violating unique index before final slug resolution.
            $createPayload['slug'] = $this->makeTemporarySlug($data['slug']);

            $product = Product::query()->create($createPayload);
            $product->update([
                'slug' => $this->resolveSlugForProduct($data['slug'], $product->id),
            ]);
            $this->syncAttributes($product, $attributes);
            $this->syncGalleryImages($product, $galleryImageUrls);
        });

        return redirect()->route('admin.products.index')->with('success', 'Product created.');
    }

    public function edit(Product $product)
    {
        $product->load('productAttributes', 'productImages');

        return view('admin.products.form', [
            'title' => 'Edit product',
            'breadcrumbs' => [
                ['label' => 'Products', 'url' => route('admin.products.index')],
                ['label' => $product->name],
            ],
            'product' => $product,
            'categories' => Category::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->resolveSlugForProduct($data['slug'], $product->id);
        $attributes = $this->extractAttributes($request);
        $thumbnailUrl = $this->storeImage($request->file('thumbnail'), 'products/thumbnails');
        $galleryImageUrls = $this->storeMultipleImages($this->normalizeUploadedFiles($request->file('images')), 'products/gallery');

        if ($thumbnailUrl !== null) {
            $this->deletePublicPath($product->thumbnail);
            $data['thumbnail'] = $thumbnailUrl;
            $data['image'] = $thumbnailUrl;
        }

        DB::transaction(function () use ($product, $data, $attributes, $galleryImageUrls): void {
            $product->update($data);
            $this->syncAttributes($product, $attributes);
            if ($galleryImageUrls->isNotEmpty()) {
                $this->syncGalleryImages($product, $galleryImageUrls);
            }
        });

        return redirect()->route('admin.products.index')->with('success', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        $product->load('productImages');
        $this->deletePublicPath($product->thumbnail);
        foreach ($product->productImages as $image) {
            $this->deletePublicPath($image->path);
        }
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'slug' => 'nullable|string|max:200',
            'category_id' => 'required|exists:categories,id',
            'short_description' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'price_usd' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'images' => 'nullable|array',
            'images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:6144',
            'meta_title' => 'nullable|string|max:190',
            'meta_description' => 'nullable|string|max:320',
            'is_active' => 'nullable|boolean',
            'attributes' => 'nullable|array',
            'attributes.*.name' => 'nullable|string|max:120',
            'attributes.*.value' => 'nullable|string|max:255',
        ]);

        $slug = $validated['slug'] ?? '';
        if ($slug === '') {
            $slug = Str::slug($validated['name']);
        }

        return [
            'name' => $validated['name'],
            'slug' => $slug ?: 'item',
            'category_id' => (int) $validated['category_id'],
            'short_description' => $validated['short_description'] ?? null,
            'description' => $validated['description'] ?? null,
            'price_usd' => (float) $validated['price_usd'],
            'stock' => (int) $validated['stock'],
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ];
    }

    /**
     * @return Collection<int, array{name: string, value: string|null}>
     */
    private function extractAttributes(Request $request): Collection
    {
        return collect($request->input('attributes', []))
            ->map(static function ($item): array {
                $name = trim((string) ($item['name'] ?? ''));
                $value = trim((string) ($item['value'] ?? ''));

                return [
                    'name' => $name,
                    'value' => $value === '' ? null : $value,
                ];
            })
            ->filter(static fn (array $item): bool => $item['name'] !== '')
            ->values();
    }

    /**
     * @param Collection<int, array{name: string, value: string|null}> $attributes
     */
    private function syncAttributes(Product $product, Collection $attributes): void
    {
        $product->productAttributes()->delete();

        if ($attributes->isEmpty()) {
            return;
        }

        $rows = $attributes->values()->map(static function (array $item, int $index) use ($product): array {
            return [
                'product_id' => $product->id,
                'name' => $item['name'],
                'value' => $item['value'],
                'sort_order' => $index,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->all();

        $product->productAttributes()->insert($rows);
    }

    /**
     * @param Collection<int, string> $paths
     */
    private function syncGalleryImages(Product $product, Collection $paths): void
    {
        foreach ($product->productImages as $image) {
            $this->deletePublicPath($image->path);
        }
        $product->productImages()->delete();

        if ($paths->isEmpty()) {
            return;
        }

        $now = Carbon::now();
        $rows = $paths->values()->map(static function (string $path, int $index) use ($product, $now): array {
            return [
                'product_id' => $product->id,
                'path' => $path,
                'sort_order' => $index,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->all();

        ProductImage::query()->insert($rows);
    }

    /**
     * @return array<int, UploadedFile|null>
     */
    private function normalizeUploadedFiles(mixed $input): array
    {
        if ($input === null) {
            return [];
        }
        if ($input instanceof UploadedFile) {
            return [$input];
        }
        if (! is_array($input)) {
            return [];
        }

        return $input;
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

    /**
     * @param array<int, UploadedFile|null> $files
     * @return Collection<int, string>
     */
    private function storeMultipleImages(array $files, string $directory): Collection
    {
        return collect($files)
            ->filter(static fn ($file): bool => $file instanceof UploadedFile)
            ->map(fn (UploadedFile $file): ?string => $this->storeImage($file, $directory))
            ->filter(static fn (?string $path): bool => $path !== null)
            ->values();
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

    private function resolveSlugForProduct(string $baseSlug, int $productId): string
    {
        $baseSlug = trim($baseSlug) !== '' ? $baseSlug : 'item';

        if (! $this->slugExists($baseSlug, $productId)) {
            return $baseSlug;
        }

        $candidate = $baseSlug.'-'.$productId;
        if (! $this->slugExists($candidate, $productId)) {
            return $candidate;
        }

        $suffix = 2;
        while ($this->slugExists($candidate.'-'.$suffix, $productId)) {
            $suffix++;
        }

        return $candidate.'-'.$suffix;
    }

    private function makeTemporarySlug(string $baseSlug): string
    {
        $baseSlug = trim($baseSlug) !== '' ? $baseSlug : 'item';

        return $baseSlug.'-tmp-'.Str::lower(Str::random(8));
    }

    private function slugExists(string $slug, int $ignoreProductId): bool
    {
        return Product::query()
            ->where('slug', $slug)
            ->where('id', '!=', $ignoreProductId)
            ->exists();
    }
}
