<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Category;
use App\Models\CurrencyRate;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    private const PUBLIC_PREFIX = '/storage/';

    /**
     * Ảnh mẫu lấy từ mạng (Lorem Picsum — URL cố định /id/, phù hợp gọi từ CLI seed).
     * Client HTTP có User-Agent để CDN phản hồi nhất quán.
     *
     * @see https://picsum.photos/
     *
     * @var array<string, string>
     */
    private const REMOTE_MEDIA = [
        'logo' => 'https://picsum.photos/id/64/512/512.jpg',
        'banner' => 'https://picsum.photos/id/1036/1920/720.jpg',
        'product_necklace_main' => 'https://picsum.photos/id/250/900/900.jpg',
        'product_necklace_g1' => 'https://picsum.photos/id/326/900/900.jpg',
        'product_necklace_g2' => 'https://picsum.photos/id/429/900/900.jpg',
        'product_bracelet_main' => 'https://picsum.photos/id/535/900/900.jpg',
        'product_bracelet_g1' => 'https://picsum.photos/id/233/900/900.jpg',
        'product_bracelet_g2' => 'https://picsum.photos/id/669/900/900.jpg',
        'product_pendant_main' => 'https://picsum.photos/id/815/900/900.jpg',
        'product_pendant_g1' => 'https://picsum.photos/id/822/900/900.jpg',
        'product_pendant_g2' => 'https://picsum.photos/id/837/900/900.jpg',
    ];

    public function run(): void
    {
        Admin::query()->updateOrCreate(
            ['email' => 'admin@gemstone.local'],
            [
                'name' => 'Store Admin',
                'password' => Hash::make('admin123'),
            ]
        );

        foreach ([
            ['USD', 'US Dollar', '$', 1.0, 0],
            ['EUR', 'Euro', '€', 0.92, 1],
            ['GBP', 'British Pound', '£', 0.79, 2],
        ] as $row) {
            CurrencyRate::query()->updateOrCreate(
                ['code' => $row[0]],
                [
                    'label' => $row[1],
                    'symbol' => $row[2],
                    'rate_per_usd' => $row[3],
                    'sort_order' => $row[4],
                    'is_active' => true,
                ]
            );
        }

        $logoPath = $this->downloadToPublicDisk(self::REMOTE_MEDIA['logo'], 'settings/logo');
        $bannerPath = $this->downloadToPublicDisk(self::REMOTE_MEDIA['banner'], 'settings/banner');

        foreach ([
            'site_name' => 'Gemstone',
            'site_logo' => $logoPath ?? '',
            'home_banner' => $bannerPath ?? '',
            'security_policy' => "We apply technical and organizational controls to protect your account and order data.\nIf you notice unusual activity, contact support immediately so we can assist and secure your account.",
            'privacy_policy' => "We collect only the information necessary to process orders, support customers, and improve service quality.\nWe do not sell personal data to third parties and only share data with trusted providers for order fulfillment.",
            'retail_policy' => "Orders are processed in business hours and shipped according to the selected shipping service.\nReturn and exchange requests are supported under our eligibility conditions and required proof of purchase.",
        ] as $key => $value) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        $c1 = Category::query()->updateOrCreate(
            ['slug' => 'heal-balance'],
            [
                'name' => 'Heal & Balance',
                'description' => 'Pieces curated for grounding and emotional harmony.',
                'meta_title' => 'Heal & Balance — Gemstone',
                'meta_description' => 'Gemstone jewelry for balance and calm.',
                'sort_order' => 1,
            ]
        );

        $c2 = Category::query()->updateOrCreate(
            ['slug' => 'lucky-charms'],
            [
                'name' => 'Lucky Charms',
                'description' => 'Symbols of fortune and protection.',
                'meta_title' => 'Lucky Charms — Gemstone',
                'meta_description' => 'Fortune and protection jewelry.',
                'sort_order' => 2,
            ]
        );

        $c3 = Category::query()->updateOrCreate(
            ['slug' => 'new-era-2026'],
            [
                'name' => 'New Era Collection',
                'description' => 'Limited designs for the new lunar cycle.',
                'meta_title' => 'New Era 2026 — Gemstone',
                'meta_description' => 'Limited gemstone designs for 2026.',
                'sort_order' => 3,
            ]
        );

        $productSpecs = [
            'agate-harmony-necklace' => [
                'category_id' => $c1->id,
                'name' => 'Agate Harmony Necklace',
                'short_description' => 'Hand-strung agate with gold-tone accents.',
                'description' => 'Emperor-grade agate necklace designed for daily wear. Each bead is selected for tone and clarity.',
                'price_usd' => 129,
                'stock' => 24,
                'meta_title' => 'Agate Harmony Necklace',
                'meta_description' => 'Premium agate necklace for harmony and style.',
                'main' => 'product_necklace_main',
                'gallery' => ['product_necklace_g1', 'product_necklace_g2'],
            ],
            'pixiu-wealth-bracelet' => [
                'category_id' => $c2->id,
                'name' => 'Pixiu Wealth Bracelet',
                'short_description' => 'Pixiu charm with obsidian beads.',
                'description' => 'Traditional Pixiu guardian paired with polished obsidian for protection and abundance energy.',
                'price_usd' => 89,
                'stock' => 40,
                'meta_title' => 'Pixiu Wealth Bracelet',
                'meta_description' => 'Obsidian bracelet with Pixiu for wealth energy.',
                'main' => 'product_bracelet_main',
                'gallery' => ['product_bracelet_g1', 'product_bracelet_g2'],
            ],
            'fire-horse-jade-pendant' => [
                'category_id' => $c3->id,
                'name' => 'Fire Horse Jade Pendant',
                'short_description' => 'Carved jade on silk cord.',
                'description' => 'Celebrating the Fire Horse — jade carved pendant blessed following classical intention-setting.',
                'price_usd' => 199,
                'stock' => 12,
                'meta_title' => 'Fire Horse Jade Pendant',
                'meta_description' => 'Limited jade pendant — New Era 2026.',
                'main' => 'product_pendant_main',
                'gallery' => ['product_pendant_g1', 'product_pendant_g2'],
            ],
        ];

        foreach ($productSpecs as $slug => $spec) {
            $mainKey = $spec['main'];
            $mainUrl = self::REMOTE_MEDIA[$mainKey] ?? null;
            $thumbPath = $mainUrl ? $this->downloadToPublicDisk($mainUrl, 'products/thumbnails') : null;

            $galleryPaths = [];
            foreach ($spec['gallery'] as $gKey) {
                $u = self::REMOTE_MEDIA[$gKey] ?? null;
                if ($u === null) {
                    continue;
                }
                $p = $this->downloadToPublicDisk($u, 'products/gallery');
                if ($p !== null) {
                    $galleryPaths[] = $p;
                }
            }

            $product = Product::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'category_id' => $spec['category_id'],
                    'name' => $spec['name'],
                    'short_description' => $spec['short_description'],
                    'description' => $spec['description'],
                    'price_usd' => $spec['price_usd'],
                    'stock' => $spec['stock'],
                    'is_active' => true,
                    'meta_title' => $spec['meta_title'],
                    'meta_description' => $spec['meta_description'],
                    'image' => $thumbPath,
                    'thumbnail' => $thumbPath,
                ]
            );

            $product->productImages()->delete();
            foreach ($galleryPaths as $index => $path) {
                ProductImage::query()->create([
                    'product_id' => $product->id,
                    'path' => $path,
                    'sort_order' => $index,
                ]);
            }
        }
    }

    private function downloadToPublicDisk(string $url, string $directory): ?string
    {
        try {
            $response = Http::timeout(90)
                ->withHeaders([
                    'User-Agent' => 'GemstoneDatabaseSeeder/1.0 (local demo)',
                    'Accept' => 'image/*,*/*;q=0.8',
                ])
                ->get($url);

            if (! $response->successful()) {
                $this->command?->warn("Seeder: HTTP {$response->status()} — {$url}");

                return null;
            }

            $binary = $response->body();
            if (strlen($binary) < 200) {
                $this->command?->warn("Seeder: response too small — {$url}");

                return null;
            }

            $ext = $this->extensionFromContentType($response->header('Content-Type'));
            $relativeDirectory = trim($directory, '/');
            $fileName = Str::uuid()->toString().'.'.$ext;
            $relative = $relativeDirectory.'/'.$fileName;

            Storage::disk('public')->put($relative, $binary);

            return self::PUBLIC_PREFIX.$relative;
        } catch (\Throwable $e) {
            $this->command?->warn('Seeder: '.$e->getMessage());

            return null;
        }
    }

    private function extensionFromContentType(?string $contentType): string
    {
        $ct = strtolower((string) $contentType);

        return match (true) {
            str_contains($ct, 'png') => 'png',
            str_contains($ct, 'webp') => 'webp',
            str_contains($ct, 'gif') => 'gif',
            str_contains($ct, 'jpeg') || str_contains($ct, 'jpg') => 'jpg',
            default => 'jpg',
        };
    }
}
