<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogBrandFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_exposes_only_active_product_category_relations_for_brand_filtering(): void
    {
        $rings = Category::query()->create([
            'name' => 'Rings',
            'slug' => 'rings',
        ]);
        $necklaces = Category::query()->create([
            'name' => 'Necklaces',
            'slug' => 'necklaces',
        ]);

        $sharedBrand = Brand::query()->create([
            'name' => 'Shared Brand',
            'slug' => 'shared-brand',
        ]);
        $ringsBrand = Brand::query()->create([
            'name' => 'Rings Brand',
            'slug' => 'rings-brand',
        ]);
        $inactiveBrand = Brand::query()->create([
            'name' => 'Inactive Brand',
            'slug' => 'inactive-brand',
        ]);

        $this->createProduct($rings, $sharedBrand, 'Shared Ring');
        $this->createProduct($necklaces, $sharedBrand, 'Shared Necklace');
        $this->createProduct($rings, $ringsBrand, 'Rings Only');
        $this->createProduct($necklaces, $inactiveBrand, 'Inactive Necklace', false);

        $response = $this->get('/catalog?category_id='.$rings->id);

        $response->assertOk();
        $response->assertSee(
            'data-brand-categories="'.$rings->id.','.$necklaces->id.'"',
            false
        );
        $response->assertSee('data-brand-categories="'.$rings->id.'"', false);
        $response->assertDontSee('Inactive Brand');
    }

    private function createProduct(
        Category $category,
        Brand $brand,
        string $name,
        bool $isActive = true
    ): Product {
        return Product::query()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => $name,
            'slug' => str($name)->slug(),
            'price_usd' => 10,
            'stock' => 1,
            'is_active' => $isActive,
        ]);
    }
}
