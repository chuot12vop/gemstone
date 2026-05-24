<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
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
        $brandId = (int) $request->get('brand_id', 0);
        $categoryId = (int) $request->get('category_id', 0);

        $query = Product::query()->with(['category', 'brand'])->latest();
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', '%'.$q.'%')
                    ->orWhere('slug', 'like', '%'.$q.'%');
            });
        }
        if ($brandId > 0) {
            $query->where('brand_id', $brandId);
        }
        if ($categoryId > 0) {
            $query->where('category_id', $categoryId);
        }

        return view('admin.products.index', [
            'title' => 'Products',
            'breadcrumbs' => [
                ['label' => 'Products'],
            ],
            'products' => $query->paginate(20)->withQueryString(),
            'q' => $q,
            'brandId' => $brandId,
            'categoryId' => $categoryId,
            'brands' => Brand::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'categories' => Category::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
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
            'brands' => Brand::query()->orderBy('sort_order')->orderBy('name')->get(),
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

        DB::transaction(function () use ($data, $attributes, $galleryImageUrls, $request): void {
            $createPayload = $data;
            // Use a temporary unique slug to avoid violating unique index before final slug resolution.
            $createPayload['slug'] = $this->makeTemporarySlug($data['slug']);

            $product = Product::query()->create($createPayload);
            $product->update([
                'slug' => $this->resolveSlugForProduct($data['slug'], $product->id),
            ]);
            $this->syncAttributes($product, $attributes);
            $this->syncGalleryImages($product, $galleryImageUrls);
            $this->syncUpsellProducts($product, $this->extractUpsells($request), $product->id);
        });

        return redirect()->route('admin.products.index')->with('success', 'Product created.');
    }

    public function edit(Product $product)
    {
        $product->load('productAttributes', 'productImages', 'upsellProducts');

        return view('admin.products.form', [
            'title' => 'Edit product',
            'breadcrumbs' => [
                ['label' => 'Products', 'url' => route('admin.products.index')],
                ['label' => $product->name],
            ],
            'product' => $product,
            'categories' => Category::query()->orderBy('sort_order')->get(),
            'brands' => Brand::query()->orderBy('sort_order')->orderBy('name')->get(),
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

        DB::transaction(function () use ($product, $data, $attributes, $galleryImageUrls, $request): void {
            $product->update($data);
            $this->syncAttributes($product, $attributes);
            if ($galleryImageUrls->isNotEmpty()) {
                $this->syncGalleryImages($product, $galleryImageUrls);
            }
            $this->syncUpsellProducts($product, $this->extractUpsells($request), $product->id);
        });

        return redirect()->route('admin.products.index')->with('success', 'Product updated.');
    }

    public function search(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $excludeId = (int) $request->get('exclude', 0);

        $query = Product::query()
            ->where('is_active', true)
            ->orderBy('name');

        if ($excludeId > 0) {
            $query->where('id', '!=', $excludeId);
        }

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', '%'.$q.'%')
                    ->orWhere('slug', 'like', '%'.$q.'%');
            });
        }

        $products = $query->limit(15)->get(['id', 'name', 'slug', 'price_usd', 'thumbnail', 'image']);

        return response()->json($products->map(static function (Product $p): array {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'price_usd' => (float) $p->price_usd,
                'thumbnail' => $p->thumbnail ?: $p->image,
            ];
        }));
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
            'brand_id' => 'required|exists:brands,id',
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
            'attributes.*.value' => 'nullable|string|max:5000',
            'upsells' => 'nullable|array',
            'upsells.*.product_id' => 'required|integer|exists:products,id|distinct',
            'upsells.*.discount' => 'nullable|numeric|min:0|max:100',
            'upsells.*.upsale_discount' => 'nullable|numeric|min:0|max:100',
        ]);

        $slug = $validated['slug'] ?? '';
        if ($slug === '') {
            $slug = Str::slug($validated['name']);
        }

        return [
            'name' => $validated['name'],
            'slug' => $slug ?: 'item',
            'category_id' => (int) $validated['category_id'],
            'brand_id' => (int) $validated['brand_id'],
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
     * @return Collection<int, array{product_id: int, discount: float, upsale_discount: float}>
     */
    private function extractUpsells(Request $request): Collection
    {
        return collect($request->input('upsells', []))
            ->map(static function ($row): array {
                return [
                    'product_id' => (int) ($row['product_id'] ?? 0),
                    'discount' => (float) ($row['discount'] ?? 0),
                    'upsale_discount' => (float) ($row['upsale_discount'] ?? 0),
                ];
            })
            ->filter(static fn (array $row): bool => $row['product_id'] > 0)
            ->unique('product_id')
            ->values();
    }

    /**
     * @param Collection<int, array{product_id: int, discount: float, upsale_discount: float}> $upsells
     */
    private function syncUpsellProducts(Product $product, Collection $upsells, int $excludeProductId): void
    {
        $sync = [];
        foreach ($upsells->values() as $index => $row) {
            $upsellId = (int) $row['product_id'];
            if ($upsellId === $excludeProductId) {
                continue;
            }
            $sync[$upsellId] = [
                'discount' => max(0, min(100, (float) $row['discount'])),
                'upsale_discount' => max(0, min(100, (float) $row['upsale_discount'])),
                'sort_order' => $index,
            ];
        }

        $product->upsellProducts()->sync($sync);
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
