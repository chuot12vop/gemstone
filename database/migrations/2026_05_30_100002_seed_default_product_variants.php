<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $products = DB::table('products')->orderBy('id')->get(['id', 'price_usd', 'stock', 'image', 'thumbnail']);

        foreach ($products as $product) {
            $exists = DB::table('product_variants')->where('product_id', $product->id)->exists();
            if ($exists) {
                continue;
            }

            $hoverImage = DB::table('product_images')
                ->where('product_id', $product->id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->skip(1)
                ->value('path');

            $image = $product->image ?: $product->thumbnail;

            DB::table('product_variants')->insert([
                'product_id' => $product->id,
                'sku' => null,
                'option_color' => null,
                'option_size' => null,
                'price_usd' => $product->price_usd,
                'compare_at_price_usd' => null,
                'stock' => $product->stock,
                'image' => $image,
                'image_hover' => $hoverImage,
                'is_default' => true,
                'is_active' => true,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('product_variants')->truncate();
    }
};
