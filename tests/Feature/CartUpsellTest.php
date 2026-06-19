<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartUpsellTest extends TestCase
{
    use RefreshDatabase;

    public function test_upsell_quantity_cannot_be_increased(): void
    {
        $category = Category::query()->create([
            'name' => 'Jewelry',
            'slug' => 'jewelry',
        ]);
        $parent = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Parent product',
            'slug' => 'parent-product',
            'price_usd' => 100,
            'stock' => 10,
            'is_active' => true,
        ]);
        $upsell = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Upsell product',
            'slug' => 'upsell-product',
            'price_usd' => 50,
            'stock' => 10,
            'is_active' => true,
        ]);
        $variant = ProductVariant::query()->create([
            'product_id' => $upsell->id,
            'price_usd' => 50,
            'stock' => 10,
            'is_default' => true,
            'is_active' => true,
        ]);

        $cart = app(CartService::class);
        $cart->add($variant->id, 3, 40, $parent->id);
        $cart->set($variant->id, 5, 40);

        $this->assertSame(1, $cart->get($variant->id)['qty']);
        $this->assertSame(1, $cart->totalQuantity());
        $this->assertTrue($cart->buildLines()[0]['is_upsell']);
    }
}
