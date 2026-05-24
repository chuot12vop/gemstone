<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Brand;
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
            'security_policy' => "We apply technical and organizational controls to protect your account and order data.\nIf you notice unusual activity, contact support immediately so we can assist and secure your account.",
            'privacy_policy' => PolicySeedBodies::privacyPolicyText(),
            'return_policy' => PolicySeedBodies::returnPolicyText(),
            'terms_of_service' => PolicySeedBodies::termsOfServiceText(),
            'payment_paypal_enabled' => '1',
            'payment_paypal_merchant_email' => 'sales@gemstone.local',
            'payment_paypal_client_id' => '',
            'payment_whatsapp_enabled' => '1',
            'payment_whatsapp_phone' => '+849xxxxxxxx',
            'payment_whatsapp_message_template' => 'Hello, I would like to pay for order #{order_number} (total {total}).',
            'payment_apple_pay_enabled' => '1',
            'payment_apple_pay_merchant_id' => '',
            'payment_apple_pay_domain' => '',
            'payment_venmo_enabled' => '1',
            'payment_venmo_username' => '',
            'payment_cashapp_enabled' => '1',
            'payment_cashapp_cashtag' => '',
            'payment_cashapp_qr_image' => '',
            'payment_zelle_enabled' => '1',
            'payment_zelle_payee_label' => 'Tachi Gem Stone',
            'payment_zelle_qr_image' => '',
        ] as $key => $value) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        $cFox = Category::query()->updateOrCreate(
            ['slug' => 'fox-queen-stone'],
            [
                'name' => 'Fox Queen Stone',
                'description' => 'Lucky Fox Queen Stone bracelets and pieces — curated colors and intentional design.',
                'meta_title' => 'Fox Queen Stone — Gemstone',
                'meta_description' => 'Lucky Fox Queen Stone collection — gemstones for balance and style.',
                'sort_order' => 1,
                'image' => env('APP_URL', '').'/storage/products/gallery/fox-queen-stone.png',
            ]
        );

        Category::query()->updateOrCreate(
            ['slug' => 'heal-balance'],
            [
                'name' => 'Heal & Balance',
                'description' => 'Pieces curated for grounding and emotional harmony.',
                'meta_title' => 'Heal & Balance — Gemstone',
                'meta_description' => 'Gemstone jewelry for balance and calm.',
                'sort_order' => 2,
                'image' => env('APP_URL', '').'/storage/products/gallery/heal-balance.webp',
            ]
        );

        $cLucky = Category::query()->updateOrCreate(
            ['slug' => 'lucky-charms'],
            [
                'name' => 'Lucky Charms',
                'description' => 'Symbols of fortune and protection.',
                'meta_title' => 'Lucky Charms — Gemstone',
                'meta_description' => 'Fortune and protection jewelry.',
                'sort_order' => 3,
                'image' => env('APP_URL', '').'/storage/products/gallery/lucky-charms.webp',
            ]
        );

        Category::query()->updateOrCreate(
            ['slug' => 'new-era-2026'],
            [
                'name' => 'New Era Collection',
                'description' => 'Limited designs for the new lunar cycle.',
                'meta_title' => 'New Era 2026 — Gemstone',
                'meta_description' => 'Limited gemstone designs for 2026.',
                'sort_order' => 4,
                'image' => env('APP_URL', '').'/storage/products/gallery/new-era-2026.webp',
            ]
        );

        $defaultSlides = [
            [
                'image' => $bannerPath,
                'title' => 'Vitality & Balance',
                'content' => 'Elevate your energy with naturally selected gemstone bracelets and handcrafted feng shui pieces.',
                'category_id' => $cFox->id,
            ],
            [
                'image' => $bannerPath,
                'title' => 'Revitalize your being',
                'content' => 'Ethically sourced stones, mindful design, and pieces made for everyday intention.',
                'category_id' => $cLucky->id,
            ],
        ];
        Setting::query()->updateOrCreate(
            ['key' => 'home_banner_slides'],
            ['value' => json_encode($defaultSlides, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
        );

        $brandTaichi = Brand::query()->updateOrCreate(
            ['slug' => 'taichi-gemstone'],
            [
                'name' => 'Taichi Gemstone',
                'sort_order' => 0,
                'image' => $logoPath,
            ]
        );
        $brandFox = Brand::query()->updateOrCreate(
            ['slug' => 'fox-queen-line'],
            [
                'name' => 'Fox Queen Line',
                'sort_order' => 1,
                'image' => env('APP_URL', '').'/storage/products/gallery/vang1.png',
            ]
        );
        $brandBalance = Brand::query()->updateOrCreate(
            ['slug' => 'balance-atelier'],
            [
                'name' => 'Balance Atelier',
                'sort_order' => 2,
                'image' => env('APP_URL', '').'/storage/products/gallery/xanh1.png',
            ]
        );

        $productSpecs = [
            'agate-harmony-necklace' => [
                'brand_id' => $brandFox->id,
                'category_id' => $cFox->id,
                'name' => 'Lucky Fox Queen Stone - Yellow',
                'short_description' => 'Hand-strung agate with gold-tone accents.',
                'description' => 'Emperor-grade agate necklace designed for daily wear. Each bead is selected for tone and clarity.',
                'price_usd' => 129,
                'stock' => 24,
                'meta_title' => 'Lucky Fox Queen Stone - Yellow',
                'meta_description' => 'Premium agate necklace for harmony and style.',
                'main' => 'product_necklace_main',
                'gallery' => [
                    env('APP_URL', '').'/storage/products/gallery/vang1.png',
                    env('APP_URL', '').'/storage/products/gallery/vang2.png',
                    env('APP_URL', '').'/storage/products/gallery/vang3.png',
                    env('APP_URL', '').'/storage/products/gallery/vang4.png',
                    env('APP_URL', '').'/storage/products/gallery/vang5.png',
                ],
                'thumbnail' => env('APP_URL', '').'/storage/products/gallery/vang1.png',
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
                'brand_id' => $brandBalance->id,
                'category_id' => $cFox->id,
                'name' => 'Lucky Fox Queen Stone - Green',
                'short_description' => 'Pixiu charm with obsidian beads.',
                'description' => 'Traditional Pixiu guardian paired with polished obsidian for protection and abundance energy.',
                'price_usd' => 89,
                'stock' => 40,
                'meta_title' => 'Lucky Fox Queen Stone - Green',
                'meta_description' => 'Obsidian bracelet with Pixiu for wealth energy.',
                'main' => 'product_bracelet_main',
                'gallery' => [
                    env('APP_URL', '').'/storage/products/gallery/xanh1.png',
                    env('APP_URL', '').'/storage/products/gallery/xanh2.png',
                    env('APP_URL', '').'/storage/products/gallery/xanh3.png',
                    env('APP_URL', '').'/storage/products/gallery/xanh4.png',
                    env('APP_URL', '').'/storage/products/gallery/xanh5.jpg',
                ],
                'thumbnail' => env('APP_URL', '').'/storage/products/gallery/xanh1.png',
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
                'brand_id' => $brandTaichi->id,
                'category_id' => $cFox->id,
                'name' => 'Lucky Fox Queen Stone - Red',
                'short_description' => 'Hand-strung agate with gold-tone accents.',
                'description' => 'Emperor-grade agate necklace designed for daily wear. Each bead is selected for tone and clarity.',
                'price_usd' => 129,
                'stock' => 24,
                'meta_title' => 'Lucky Fox Queen Stone - Red',
                'meta_description' => 'Premium agate necklace for harmony and style.',
                'main' => 'product_necklace_main',
                'gallery' => [
                    env('APP_URL', '').'/storage/products/gallery/do1.png',
                    env('APP_URL', '').'/storage/products/gallery/do2.png',
                    env('APP_URL', '').'/storage/products/gallery/do3.png',
                    env('APP_URL', '').'/storage/products/gallery/do4.png',
                    env('APP_URL', '').'/storage/products/gallery/do5.png',
                ],
                'thumbnail' => env('APP_URL', '').'/storage/products/gallery/do1.png',
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
                'brand_id' => $brandFox->id,
                'category_id' => $cFox->id,
                'name' => 'Lucky Fox Queen Stone - White',
                'short_description' => 'Carved jade on silk cord.',
                'description' => 'Celebrating the Fire Horse — jade carved pendant blessed following classical intention-setting.',
                'price_usd' => 199,
                'stock' => 12,
                'meta_title' => 'Lucky Fox Queen Stone - White',
                'meta_description' => 'Limited jade pendant — New Era 2026.',
                'main' => 'product_pendant_main',
                'gallery' => [
                    env('APP_URL', '').'/storage/products/gallery/trang1.png',
                    env('APP_URL', '').'/storage/products/gallery/trang2.png',
                    env('APP_URL', '').'/storage/products/gallery/trang3.png',
                    env('APP_URL', '').'/storage/products/gallery/trang4.png',
                    env('APP_URL', '').'/storage/products/gallery/trang5.png',
                ],
                'thumbnail' => env('APP_URL', '').'/storage/products/gallery/trang1.png',
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
                    'brand_id' => $spec['brand_id'],
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

        $this->call(CertificateSeeder::class);
        $this->call(PostSeeder::class);
    }
}
