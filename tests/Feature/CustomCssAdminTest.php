<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Support\CustomThemeStylesheet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class CustomCssAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_guest_cannot_access_or_update_custom_css(): void
    {
        $this->get(route('admin.custom-css.index'))->assertRedirect(route('admin.login'));
        $this->put(route('admin.custom-css.update', 'mobile'), ['custom_css' => 'body {}'])
            ->assertRedirect(route('admin.login'));

        Storage::disk('public')->assertMissing('custom-theme-mobile.css');
    }

    public function test_admin_page_reads_all_stylesheets_and_exposes_viewport_sizes(): void
    {
        Storage::disk('public')->put('custom-theme.css', '.desktop-rule {}');
        Storage::disk('public')->put('custom-theme-mobile.css', '.mobile-rule {}');
        Storage::disk('public')->put('custom-theme-tablet.css', '.tablet-rule {}');

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.custom-css.index'))
            ->assertOk()
            ->assertSee('.desktop-rule {}')
            ->assertSee('.mobile-rule {}')
            ->assertSee('.tablet-rule {}')
            ->assertSee('data-width="430"', false)
            ->assertSee('data-height="932"', false)
            ->assertSee('data-width="1024"', false)
            ->assertSee('data-height="1366"', false);
    }

    public function test_admin_can_save_one_viewport_without_changing_the_other_files(): void
    {
        Storage::disk('public')->put('custom-theme.css', 'desktop');
        Storage::disk('public')->put('custom-theme-tablet.css', 'tablet');

        $css = "body {\n  color: #123456;\n}\n";
        $this->actingAs($this->admin(), 'admin')
            ->post(
                route('admin.custom-css.update', 'mobile'),
                ['_method' => 'PUT', 'custom_css' => $css],
                ['Accept' => 'application/json']
            )
            ->assertOk()
            ->assertJsonPath('viewport', 'mobile');

        $this->assertSame($css, Storage::disk('public')->get('custom-theme-mobile.css'));
        $this->assertSame('desktop', Storage::disk('public')->get('custom-theme.css'));
        $this->assertSame('tablet', Storage::disk('public')->get('custom-theme-tablet.css'));
    }

    public function test_admin_can_create_an_empty_viewport_stylesheet(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.custom-css.update', 'tablet'), ['custom_css' => ''])
            ->assertRedirect(route('admin.custom-css.index'));

        Storage::disk('public')->assertExists('custom-theme-tablet.css');
        $this->assertSame('', Storage::disk('public')->get('custom-theme-tablet.css'));
    }

    public function test_invalid_viewport_is_not_accepted(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->put('/admin/custom-css/watch', ['custom_css' => 'body {}'])
            ->assertNotFound();
    }

    public function test_oversized_css_is_rejected_without_overwriting_the_target_file(): void
    {
        Storage::disk('public')->put('custom-theme-mobile.css', 'keep me');

        $this->actingAs($this->admin(), 'admin')
            ->putJson(route('admin.custom-css.update', 'mobile'), [
                'custom_css' => str_repeat('a', CustomThemeStylesheet::MAX_BYTES + 1),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('custom_css');

        $this->assertSame('keep me', Storage::disk('public')->get('custom-theme-mobile.css'));
    }

    public function test_write_failure_returns_a_json_error_for_the_target_file(): void
    {
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('put')
            ->once()
            ->with('custom-theme-tablet.css', 'body {}')
            ->andReturnFalse();
        Storage::shouldReceive('disk')->once()->with('public')->andReturn($disk);

        $this->actingAs($this->admin(), 'admin')
            ->putJson(route('admin.custom-css.update', 'tablet'), ['custom_css' => 'body {}'])
            ->assertStatus(500)
            ->assertJsonPath('errors.custom_css.0', 'Unable to write the custom CSS file. Please check storage permissions.');
    }

    public function test_storefront_layouts_include_versioned_responsive_stylesheets_in_load_order(): void
    {
        $withoutCss = $this->renderShopLayout();
        $this->assertStringNotContainsString('data-custom-theme-stylesheet', $withoutCss);

        foreach (CustomThemeStylesheet::VIEWPORTS as $config) {
            Storage::disk('public')->put($config['path'], 'body {}');
        }

        $shopLayout = $this->renderShopLayout();
        $checkoutLayout = $this->renderCheckoutLayout();
        foreach ([$shopLayout, $checkoutLayout] as $layout) {
            $desktop = strpos($layout, 'data-custom-theme-stylesheet="desktop"');
            $tablet = strpos($layout, 'data-custom-theme-stylesheet="tablet"');
            $mobile = strpos($layout, 'data-custom-theme-stylesheet="mobile"');

            $this->assertIsInt($desktop);
            $this->assertTrue($desktop < $tablet && $tablet < $mobile);
            $this->assertStringContainsString('media="(max-width: 767px)"', $layout);
            $this->assertStringContainsString('media="(min-width: 768px) and (max-width: 1024px)"', $layout);
            $this->assertMatchesRegularExpression('/custom-theme-mobile\\.css\\?v=\\d+/', $layout);
            $this->assertMatchesRegularExpression('/custom-theme-tablet\\.css\\?v=\\d+/', $layout);
        }
    }

    private function admin(): Admin
    {
        return Admin::query()->create([
            'name' => 'Theme Admin',
            'email' => uniqid('theme-', true).'@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    private function renderShopLayout(): string
    {
        return view('layouts.shop', [
            'currency' => app(\App\Services\CurrencyService::class),
            'siteSettings' => [],
            'shopFront' => [],
            'paymentLogos' => [],
            'catalogNavCategories' => collect(),
        ])->render();
    }

    private function renderCheckoutLayout(): string
    {
        return view('layouts.checkout', [
            'currency' => app(\App\Services\CurrencyService::class),
            'siteSettings' => [],
            'paymentLogos' => [],
        ])->render();
    }
}
