<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ProductVariantHoverImage;
use App\Services\PublicImageStore;
use App\Support\ProductVariantOptions;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductAdminController extends Controller
{
    private PublicImageStore $images;

    public function __construct(PublicImageStore $images)
    {
        $this->images = $images;
    }

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
        $thumbnailUrl = $this->images->store($request->file('thumbnail'), 'products/thumbnails', asWebp: true);
        $galleryImageUrls = $this->images->storeMany($this->normalizeUploadedFiles($request->file('images')), 'products/gallery');
        if ($thumbnailUrl !== null) {
            $data['thumbnail'] = $thumbnailUrl;
            $data['image'] = $thumbnailUrl;
        }
        $this->applyStickerUpload($request, $data);

        $variants = $this->extractVariants($request);

        DB::transaction(function () use ($data, $attributes, $galleryImageUrls, $request, $variants): void {
            $createPayload = $data;
            // Use a temporary unique slug to avoid violating unique index before final slug resolution.
            $createPayload['slug'] = $this->makeTemporarySlug($data['slug']);

            $product = Product::query()->create($createPayload);
            $product->update([
                'slug' => $this->resolveSlugForProduct($data['slug'], $product->id),
            ]);
            $this->syncAttributes($product, $attributes);
            $this->syncGalleryImages($product, $galleryImageUrls);
            $this->syncVariants($product, $variants, $request);
            $this->syncUpsellProducts($product, $this->extractUpsells($request), $product->id);
        });

        return redirect()->route('admin.products.index')->with('success', 'Product created.');
    }

    public function edit(Product $product)
    {
        $product->load('productAttributes', 'productImages', 'upsellProducts', 'variants.hoverImages');

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
        $thumbnailUrl = $this->images->store($request->file('thumbnail'), 'products/thumbnails', asWebp: true);
        $galleryImageUrls = $this->images->storeMany($this->normalizeUploadedFiles($request->file('images')), 'products/gallery');

        if ($thumbnailUrl !== null) {
            $this->images->delete($product->thumbnail);
            $data['thumbnail'] = $thumbnailUrl;
            $data['image'] = $thumbnailUrl;
        }
        $this->applyStickerUpload($request, $data, $product);

        $variants = $this->extractVariants($request);

        DB::transaction(function () use ($product, $data, $attributes, $galleryImageUrls, $request, $variants): void {
            $product->update($data);
            $this->syncAttributes($product, $attributes);
            if ($galleryImageUrls->isNotEmpty()) {
                $this->syncGalleryImages($product, $galleryImageUrls);
            }
            $this->syncVariants($product, $variants, $request);
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
        $this->images->delete($product->thumbnail);
        $this->images->delete($product->sticker);
        foreach ($product->productImages as $image) {
            $this->images->delete($image->path);
        }
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $this->normalizeVariantInputs($request);

        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'slug' => 'nullable|string|max:200',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'required|exists:brands,id',
            'short_description' => 'nullable|string|max:500',
            'card_badge_label' => 'nullable|string|max:50',
            'discount' => 'nullable|numeric|min:0|max:100',
            'sticker' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'remove_sticker' => 'nullable|boolean',
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
            'variants' => 'required|array|min:1',
            'variants.*.id' => 'nullable|integer|exists:product_variants,id',
            'variants.*.option_color' => 'nullable|string|max:100',
            'variants.*.swatch_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'variants.*.option_size' => 'nullable|string|max:100',
            'variants.*.price_usd' => 'required|numeric|min:0',
            'variants.*.compare_at_price_usd' => 'nullable|numeric|min:0',
            'variants.*.stock' => 'required|integer|min:0',
            'variants.*.sku' => 'nullable|string|max:100',
            'variants.*.is_default' => 'nullable|boolean',
            'variants.*.is_active' => 'nullable|boolean',
            'variants.*.image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:6144',
            'variants.*.image_hover' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:6144',
            'variants.*.hover_images' => 'nullable|array|max:5',
            'variants.*.hover_images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:6144',
            'variants.*.remove_hover_image_ids' => 'nullable|array',
            'variants.*.remove_hover_image_ids.*' => 'integer|exists:product_variant_hover_images,id',
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
            'card_badge_label' => trim((string) ($validated['card_badge_label'] ?? '')) ?: null,
            'discount' => isset($validated['discount']) && $validated['discount'] !== '' && (float) $validated['discount'] > 0
                ? (float) $validated['discount']
                : null,
            'description' => $validated['description'] ?? null,
            'price_usd' => (float) $validated['price_usd'],
            'stock' => (int) $validated['stock'],
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function extractVariants(Request $request): Collection
    {
        return collect($request->input('variants', []))
            ->map(static function ($row, int $index): array {
                return [
                    'id' => isset($row['id']) ? (int) $row['id'] : null,
                    'option_color' => trim((string) ($row['option_color'] ?? '')) ?: null,
                    'swatch_color' => self::normalizeSwatchColor($row['swatch_color'] ?? null),
                    'option_size' => trim((string) ($row['option_size'] ?? '')) ?: null,
                    'price_usd' => (float) ($row['price_usd'] ?? 0),
                    'compare_at_price_usd' => isset($row['compare_at_price_usd']) && $row['compare_at_price_usd'] !== ''
                        ? (float) $row['compare_at_price_usd']
                        : null,
                    'stock' => (int) ($row['stock'] ?? 0),
                    'sku' => trim((string) ($row['sku'] ?? '')) ?: null,
                    'is_default' => ! empty($row['is_default']),
                    'is_active' => ! array_key_exists('is_active', $row) || ! empty($row['is_active']),
                    'sort_order' => $index,
                ];
            })
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $variants
     */
    private function syncVariants(Product $product, Collection $variants, Request $request): void
    {
        if ($variants->isEmpty()) {
            return;
        }

        $defaultCount = $variants->where('is_default', true)->count();
        if ($defaultCount !== 1) {
            $variants = $variants->map(function (array $row, int $index) {
                $row['is_default'] = $index === 0;

                return $row;
            });
        }

        $existingIds = $product->variants()->pluck('id')->all();
        $keptIds = [];

        foreach ($variants as $index => $row) {
            $variantId = $row['id'] ?? null;
            $variant = null;

            if ($variantId && in_array($variantId, $existingIds, true)) {
                $variant = ProductVariant::query()
                    ->where('product_id', $product->id)
                    ->where('id', $variantId)
                    ->first();
            }

            $imageFile = $request->file("variants.{$index}.image");
            $hoverFile = $request->file("variants.{$index}.image_hover");
            $imageUrl = $this->images->store($imageFile, 'products/variants', asWebp: true);
            $hoverUrl = $this->images->store($hoverFile, 'products/variants', asWebp: true);

            $payload = [
                'option_color' => $row['option_color'],
                'swatch_color' => $row['swatch_color'],
                'option_size' => $row['option_size'],
                'price_usd' => $row['price_usd'],
                'compare_at_price_usd' => $row['compare_at_price_usd'],
                'stock' => $row['stock'],
                'sku' => $row['sku'],
                'is_default' => (bool) $row['is_default'],
                'is_active' => (bool) $row['is_active'],
                'sort_order' => (int) $row['sort_order'],
            ];

            if ($variant) {
                if ($imageUrl !== null) {
                    $this->images->delete($variant->image);
                    $payload['image'] = $imageUrl;
                }
                if ($hoverUrl !== null) {
                    $this->images->delete($variant->image_hover);
                    $payload['image_hover'] = $hoverUrl;
                }
                $variant->update($payload);
                $keptIds[] = $variant->id;
            } else {
                if ($imageUrl !== null) {
                    $payload['image'] = $imageUrl;
                } elseif ($product->image) {
                    $payload['image'] = $product->image;
                }
                if ($hoverUrl !== null) {
                    $payload['image_hover'] = $hoverUrl;
                }
                $variant = $product->variants()->create($payload);
                $keptIds[] = $variant->id;
            }

            $this->syncVariantHoverImages($variant, $request, $index, $row);
        }

        $removeIds = array_diff($existingIds, $keptIds);
        if ($removeIds !== []) {
            $toRemove = ProductVariant::query()->whereIn('id', $removeIds)->with('hoverImages')->get();
            foreach ($toRemove as $variant) {
                $this->images->delete($variant->image);
                $this->images->delete($variant->image_hover);
                foreach ($variant->hoverImages as $hoverImage) {
                    $this->images->delete($hoverImage->path);
                }
                $variant->delete();
            }
        }

        $product->load('variants');
        ProductVariantOptions::syncProductDenormalized($product, $product->variants);
    }

    private function normalizeVariantInputs(Request $request): void
    {
        $variants = $request->input('variants', []);
        if (! is_array($variants)) {
            return;
        }

        foreach ($variants as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            if (array_key_exists('swatch_color', $row) && trim((string) $row['swatch_color']) === '') {
                $variants[$index]['swatch_color'] = null;
            }
        }

        $request->merge(['variants' => $variants]);
    }

    private static function normalizeSwatchColor(mixed $value): ?string
    {
        $hex = trim((string) ($value ?? ''));

        return preg_match('/^#[0-9A-Fa-f]{6}$/', $hex) ? strtolower($hex) : null;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function syncVariantHoverImages(ProductVariant $variant, Request $request, int $index, array $row): void
    {
        $removeIds = collect($row['remove_hover_image_ids'] ?? [])
            ->map(static fn ($id) => (int) $id)
            ->filter()
            ->all();

        if ($removeIds !== []) {
            $toRemove = $variant->hoverImages()->whereIn('id', $removeIds)->get();
            foreach ($toRemove as $hoverImage) {
                $this->images->delete($hoverImage->path);
                $hoverImage->delete();
            }
        }

        $existingCount = $variant->hoverImages()->count();
        $uploads = $request->file("variants.{$index}.hover_images") ?? [];
        if (! is_array($uploads)) {
            $uploads = [$uploads];
        }

        $sortOrder = $existingCount;
        foreach ($uploads as $upload) {
            if (! $upload instanceof UploadedFile || ! $upload->isValid()) {
                continue;
            }
            if ($sortOrder >= 5) {
                break;
            }

            $path = $this->images->store($upload, 'products/variants/hover', asWebp: true);
            if ($path === null) {
                continue;
            }

            ProductVariantHoverImage::query()->create([
                'product_variant_id' => $variant->id,
                'path' => $path,
                'sort_order' => $sortOrder,
            ]);
            $sortOrder++;
        }

        $legacyHover = $request->file("variants.{$index}.image_hover");
        if ($legacyHover instanceof UploadedFile && $legacyHover->isValid() && $sortOrder < 5) {
            $path = $this->images->store($legacyHover, 'products/variants/hover', asWebp: true);
            if ($path !== null) {
                ProductVariantHoverImage::query()->create([
                    'product_variant_id' => $variant->id,
                    'path' => $path,
                    'sort_order' => $sortOrder,
                ]);
            }
        }
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
            $this->images->delete($image->path);
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

    /**
     * @param  array<string, mixed>  $data
     */
    private function applyStickerUpload(Request $request, array &$data, ?Product $product = null): void
    {
        if ($product !== null && $request->boolean('remove_sticker')) {
            $this->images->delete($product->sticker);
            $data['sticker'] = null;

            return;
        }

        $stickerUrl = $this->images->store($request->file('sticker'), 'products/stickers', asWebp: true);
        if ($stickerUrl === null) {
            return;
        }

        if ($product !== null) {
            $this->images->delete($product->sticker);
        }

        $data['sticker'] = $stickerUrl;
    }
}
