<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Category;
use App\Models\CurrencyRate;
use App\Models\Product;
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

        Product::query()->updateOrCreate(
            ['slug' => 'agate-harmony-necklace'],
            [
                'category_id' => $c1->id,
                'name' => 'Agate Harmony Necklace',
                'short_description' => 'Hand-strung agate with gold-tone accents.',
                'description' => 'Emperor-grade agate necklace designed for daily wear. Each bead is selected for tone and clarity.',
                'price_usd' => 129,
                'stock' => 24,
                'is_active' => true,
                'meta_title' => 'Agate Harmony Necklace',
                'meta_description' => 'Premium agate necklace for harmony and style.',
            ]
        );

        Product::query()->updateOrCreate(
            ['slug' => 'pixiu-wealth-bracelet'],
            [
                'category_id' => $c2->id,
                'name' => 'Pixiu Wealth Bracelet',
                'short_description' => 'Pixiu charm with obsidian beads.',
                'description' => 'Traditional Pixiu guardian paired with polished obsidian for protection and abundance energy.',
                'price_usd' => 89,
                'stock' => 40,
                'is_active' => true,
                'meta_title' => 'Pixiu Wealth Bracelet',
                'meta_description' => 'Obsidian bracelet with Pixiu for wealth energy.',
            ]
        );

        Product::query()->updateOrCreate(
            ['slug' => 'fire-horse-jade-pendant'],
            [
                'category_id' => $c3->id,
                'name' => 'Fire Horse Jade Pendant',
                'short_description' => 'Carved jade on silk cord.',
                'description' => 'Celebrating the Fire Horse — jade carved pendant blessed following classical intention-setting.',
                'price_usd' => 199,
                'stock' => 12,
                'is_active' => true,
                'meta_title' => 'Fire Horse Jade Pendant',
                'meta_description' => 'Limited jade pendant — New Era 2026.',
            ]
        );
    }
}
