<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogSortSelectionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider sortOptions
     */
    public function test_catalog_toolbar_keeps_the_selected_sort_after_loading(
        string $sort,
        string $label
    ): void {
        $response = $this->get('/product?sort='.$sort);

        $response->assertOk();
        $response->assertSee(
            '<option value="'.$sort.'" selected>'.$label.'</option>',
            false
        );
    }

    public static function sortOptions(): array
    {
        return [
            'newest' => ['newest', 'Newest'],
            'featured' => ['related', 'Featured'],
            'price high to low' => ['price_desc', 'Price: high to low'],
            'price low to high' => ['price_asc', 'Price: low to high'],
        ];
    }
}
