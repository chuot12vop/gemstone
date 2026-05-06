<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Category;
use App\Models\CurrencyRate;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductImage;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
class DatabaseSeeder extends Seeder
{

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

        $logoPath = '/storage/settings/logo/logo.jpg';
        $bannerPath = '/storage/settings/banner/banner.webp';

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
                'name' => 'Lucky Fox Queen Stone - Yellow',
                'short_description' => 'Hand-strung agate with gold-tone accents.',
                'description' => 'Emperor-grade agate necklace designed for daily wear. Each bead is selected for tone and clarity.',
                'price_usd' => 129,
                'stock' => 24,
                'meta_title' => 'Lucky Fox Queen Stone - Yellow',
                'meta_description' => 'Premium agate necklace for harmony and style.',
                'main' => 'product_necklace_main',
                'gallery' => [
                    env('APP_URL', '').'/storage/products/gallery/vang-1.webp',
                    env('APP_URL', '').'/storage/products/gallery/vang-2.webp',
                    env('APP_URL', '').'/storage/products/gallery/vang-3.webp',
                    env('APP_URL', '').'/storage/products/gallery/vang-4.avif',
                    env('APP_URL', '').'/storage/products/gallery/vang-5.webp',
                ],
                'thumbnail' => env('APP_URL', '').'/storage/products/thumbnails/vang-1.webp',
                'attributes' => [
                    'color' => 'Yellow',
                    'size' => '18k',
                    'uses' => 'It calms the nerves, creates a feeling of tranquility, and harmonizes with nature.',
                    'material' => 'Agate',
                    'history' => 'The Pixiu is a mythical creature with a body like a lion and a dragon\'s head. It is said to be able to ward off evil and bring good luck.',
                    'origin' => 'China',
                ],
            ],
            'pixiu-wealth-bracelet' => [
                'category_id' => $c2->id,
                'name' => 'Lucky Fox Queen Stone - Green',
                'short_description' => 'Pixiu charm with obsidian beads.',
                'description' => 'Traditional Pixiu guardian paired with polished obsidian for protection and abundance energy.',
                'price_usd' => 89,
                'stock' => 40,
                'meta_title' => 'Lucky Fox Queen Stone - Green',
                'meta_description' => 'Obsidian bracelet with Pixiu for wealth energy.',
                'main' => 'product_bracelet_main',
                'gallery' => [
                    env('APP_URL', '').'/storage/products/gallery/xanh-1.webp',
                    env('APP_URL', '').'/storage/products/gallery/xanh-2.webp',
                    env('APP_URL', '').'/storage/products/gallery/xanh-3.webp',
                    env('APP_URL', '').'/storage/products/gallery/xanh-4.avif',
                    env('APP_URL', '').'/storage/products/gallery/xanh-5.webp',
                ],
                'thumbnail' => env('APP_URL', '').'/storage/products/thumbnails/xanh-1.webp',
                'attributes' => [
                    'color' => 'Green',
                    'size' => '18k',
                    'uses' => 'It is said to be able to ward off evil and bring good luck.',
                    'material' => 'Obsidian',
                    'history' => 'Obsidian is a natural volcanic glass that is said to be able to ward off evil and bring good luck.',
                    'origin' => 'Mexico',
                ],
            ],
            'lucky-fox-queen-stone-red' => [
                'category_id' => $c2->id,
                'name' => 'Lucky Fox Queen Stone - Red',
                'short_description' => 'Hand-strung agate with gold-tone accents.',
                'description' => 'Emperor-grade agate necklace designed for daily wear. Each bead is selected for tone and clarity.',
                'price_usd' => 129,
                'stock' => 24,
                'meta_title' => 'Lucky Fox Queen Stone - Red',
                'meta_description' => 'Premium agate necklace for harmony and style.',
                'main' => 'product_necklace_main',
                'gallery' => [
                    env('APP_URL', '').'/storage/products/gallery/do-1.webp',
                    env('APP_URL', '').'/storage/products/gallery/do-2.webp',
                    env('APP_URL', '').'/storage/products/gallery/do-3.webp',
                    env('APP_URL', '').'/storage/products/gallery/do-4.avif',
                    env('APP_URL', '').'/storage/products/gallery/do-5.webp',
                ],
                'thumbnail' => env('APP_URL', '').'/storage/products/thumbnails/do-1.webp',
                'attributes' => [
                    'color' => 'Red',
                    'size' => '18k',
                    'uses' => 'It is said to be able to ward off evil and bring good luck.',
                    'material' => 'Agate',
                    'history' => 'Agate is a type of quartz that is said to be able to ward off evil and bring good luck.',
                    'origin' => 'Brazil',
                ],
            ],
            'fire-horse-jade-pendant' => [
                'category_id' => $c3->id,
                'name' => 'Lucky Fox Queen Stone - White',
                'short_description' => 'Carved jade on silk cord.',
                'description' => 'Celebrating the Fire Horse — jade carved pendant blessed following classical intention-setting.',
                'price_usd' => 199,
                'stock' => 12,
                'meta_title' => 'Lucky Fox Queen Stone - White',
                'meta_description' => 'Limited jade pendant — New Era 2026.',
                'main' => 'product_pendant_main',
                'gallery' => [
                    env('APP_URL', '').'/storage/products/gallery/trang-1.webp',
                    env('APP_URL', '').'/storage/products/gallery/trang-2.webp',
                    env('APP_URL', '').'/storage/products/gallery/trang-3.webp',
                    env('APP_URL', '').'/storage/products/gallery/trang-4.avif',
                    env('APP_URL', '').'/storage/products/gallery/trang-5.webp',
                ],
                'thumbnail' => env('APP_URL', '').'/storage/products/thumbnails/trang-1.webp',
                'attributes' => [
                    'color' => 'White',
                    'size' => '18k',
                    'uses' => 'It is said to be able to ward off evil and bring good luck.',
                    'material' => 'Jade',
                    'history' => 'Jade is a type of stone that is said to be able to ward off evil and bring good luck.',
                    'origin' => 'China',
                ],
            ],
        ];

        foreach ($productSpecs as $slug => $spec) {
            $thumbPath = $spec['thumbnail'] ?? null;
            $galleryPaths = $spec['gallery'] ?? [];
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

            $product->productAttributes()->delete();
            foreach ($spec['attributes'] as $attribute => $value) {
                ProductAttribute::query()->create([
                    'product_id' => $product->id,
                    'name' => $attribute,
                    'value' => $value,
                    'sort_order' => 1,
                ]);
            }
        }
    }
}
