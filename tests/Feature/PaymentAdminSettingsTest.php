<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentAdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_saves_shared_paypal_settings_and_keeps_existing_secret(): void
    {
        Setting::query()->create(['key' => 'payment_paypal_client_secret', 'value' => 'keep-secret']);

        $this->actingAs($this->admin(), 'admin')->post(route('admin.payments.settings'), [
            'card_enabled' => '1',
            'paypal_enabled' => '1',
            'apple_pay_enabled' => '1',
            'paypal_merchant_email' => 'merchant@example.com',
            'paypal_client_id' => 'client-id',
            'paypal_client_secret' => '',
            'paypal_webhook_id' => 'WEBHOOK-1',
            'paypal_sandbox' => '1',
        ])->assertRedirect(route('admin.payments.index', ['tab' => 'settings']));

        $this->assertSame('keep-secret', Setting::query()->where('key', 'payment_paypal_client_secret')->value('value'));
        $this->assertSame('WEBHOOK-1', Setting::query()->where('key', 'payment_paypal_webhook_id')->value('value'));
        $this->assertSame('1', Setting::query()->where('key', 'payment_card_enabled')->value('value'));
        $this->assertSame('1', Setting::query()->where('key', 'payment_apple_pay_enabled')->value('value'));
        $this->assertDatabaseMissing('settings', ['key' => 'payment_apple_pay_stripe_secret_key']);
    }

    public function test_payment_settings_page_has_no_stripe_fields(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.payments.index', ['tab' => 'settings']))
            ->assertOk()
            ->assertSee('Credit or Debit Card (PayPal)')
            ->assertSee('Apple Pay (PayPal)')
            ->assertSee('paypal_webhook_id', false)
            ->assertDontSee('Stripe')
            ->assertDontSee('apple_pay_stripe', false);
    }

    private function admin(): Admin
    {
        return Admin::query()->create([
            'name' => 'Payments Admin',
            'email' => uniqid('payments-', true).'@example.com',
            'password' => bcrypt('password'),
        ]);
    }
}
