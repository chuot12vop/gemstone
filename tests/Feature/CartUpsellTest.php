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

    public function test_cart_lines_use_product_discount_for_regular_items(): void
    {
        $category = Category::query()->create([
            'name' => 'Jewelry',
            'slug' => 'jewelry',
        ]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Sale product',
            'slug' => 'sale-product',
            'price_usd' => 100,
            'discount' => 20,
            'stock' => 10,
            'is_active' => true,
        ]);
        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'price_usd' => 100,
            'stock' => 10,
            'is_default' => true,
            'is_active' => true,
        ]);

        $cart = app(CartService::class);
        $cart->add($variant->id, 2);

        $line = $cart->buildLines()[0];
        $this->assertSame(80.0, $line['unit_price_usd']);
        $this->assertSame(100.0, $line['compare_unit_price_usd']);
        $this->assertSame(160.0, $line['line_usd']);
        $this->assertSame(200.0, $line['compare_line_usd']);
    }

    public function test_bundle_uses_parent_discount_and_upsell_pair_discount(): void
    {
        $category = Category::query()->create([
            'name' => 'Jewelry',
            'slug' => 'jewelry',
        ]);
        $parent = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Parent product',
            'slug' => 'bundle-parent-product',
            'price_usd' => 100,
            'discount' => 20,
            'stock' => 10,
            'is_active' => true,
        ]);
        $upsell = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Upsell product',
            'slug' => 'bundle-upsell-product',
            'price_usd' => 50,
            'discount' => 10,
            'stock' => 10,
            'is_active' => true,
        ]);
        $parentVariant = ProductVariant::query()->create([
            'product_id' => $parent->id,
            'price_usd' => 100,
            'stock' => 10,
            'is_default' => true,
            'is_active' => true,
        ]);
        $upsellVariant = ProductVariant::query()->create([
            'product_id' => $upsell->id,
            'price_usd' => 50,
            'stock' => 10,
            'is_default' => true,
            'is_active' => true,
        ]);
        $parent->upsellProducts()->attach($upsell->id, [
            'discount' => 5,
            'upsale_discount' => 30,
            'sort_order' => 1,
        ]);

        $response = $this->post(route('shop.cart.add-bundle'), [
            'parent_product_id' => $parent->id,
            'items' => [
                $parent->id => [
                    'product_id' => $parent->id,
                    'variant_id' => $parentVariant->id,
                    'quantity' => 1,
                ],
                $upsell->id => [
                    'product_id' => $upsell->id,
                    'variant_id' => $upsellVariant->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertRedirect(route('shop.cart'));

        $lines = collect(app(CartService::class)->buildLines())->keyBy(fn (array $line) => $line['product']->id);
        $this->assertSame(80.0, $lines[$parent->id]['unit_price_usd']);
        $this->assertSame(100.0, $lines[$parent->id]['compare_unit_price_usd']);
        $this->assertSame(35.0, $lines[$upsell->id]['unit_price_usd']);
        $this->assertSame(50.0, $lines[$upsell->id]['compare_unit_price_usd']);
        $this->assertSame(115.0, (float) $lines->sum('line_usd'));
    }
}
