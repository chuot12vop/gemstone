<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Services\CartService;
use App\Services\Payment\Gateways\ApplePayGateway;
use App\Services\Payment\Gateways\CardGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CardPaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_renders_all_paypal_backed_online_methods(): void
    {
        $this->enablePayments();
        $this->addProductToCart();
        $this->fakeInitiation('PAYPAL-CHECKOUT-FIELDS');

        $this->get(route('shop.checkout'))
            ->assertOk()
            ->assertSee('data-checkout-loading', false)
            ->assertSee('aria-live="polite"', false)
            ->assertSeeInOrder(['data-checkout-discount-row', 'hidden'], false)
            ->assertSee('Processing your payment...')
            ->assertSee('Credit or Debit Card')
            ->assertSee('PayPal')
            ->assertSee('Apple Pay')
            ->assertSee('id="express-applepay-button"', false)
            ->assertSee('data-paypal-web-sdk="https://www.sandbox.paypal.com/web-sdk/v6/core"', false)
            ->assertSee('data-paypal-client-id="paypal-client-id"', false)
            ->assertSee('data-paypal-sandbox="1"', false)
            ->assertSee('data-apple-pay-amount="130.14"', false)
            ->assertSee('data-checkout-card-fields', false)
            ->assertSee('id="checkout-card-number"', false)
            ->assertSee('id="payment-more-panel"', false)
            ->assertDontSee('data-wallet-preload="1"', false)
            ->assertSee('id="checkout-paypal-button"', false)
            ->assertSee('id="checkout-applepay-button"', false)
            ->assertDontSee('data-client-token=', false)
            ->assertDontSee('components=buttons%2Cgooglepay%2Capplepay%2Ccard-fields', false)
            ->assertDontSee('enable-funding', false)
            ->assertSee('assets/img/payments/raster/visa.png')
            ->assertDontSee('Stripe');
    }

    public function test_checkout_hides_express_apple_pay_when_it_is_disabled(): void
    {
        $this->enablePayments();
        Setting::query()->where('key', 'payment_apple_pay_enabled')->update(['value' => '0']);
        $this->addProductToCart();
        $this->fakeInitiation('PAYPAL-CHECKOUT-FIELDS');

        $this->get(route('shop.checkout'))
            ->assertOk()
            ->assertDontSee('id="express-applepay-button"', false)
            ->assertSee('/web-sdk/v6/core', false)
            ->assertDontSee('enable-funding', false)
            ->assertDontSee('%2Capplepay', false);
    }

    public function test_card_checkout_can_create_an_order_for_embedded_card_fields(): void
    {
        $this->enablePayments();
        $this->addProductToCart();
        $this->fakeInitiation('PAYPAL-EMBEDDED-CARD-1');

        $this->postJson(route('shop.checkout.place'), $this->checkoutPayload('card'))
            ->assertOk()
            ->assertJsonPath('paypal_order_id', 'PAYPAL-EMBEDDED-CARD-1')
            ->assertJsonStructure(['order_number', 'confirm_url']);

        $this->assertSame('PAYPAL-EMBEDDED-CARD-1', PaymentTransaction::query()->firstOrFail()->gateway_transaction_id);
    }

    public function test_card_checkout_stores_paypal_v6_billing_address_shape(): void
    {
        $this->enablePayments();
        $this->addProductToCart();
        $this->fakeInitiation('PAYPAL-BILLING-1');

        $payload = $this->checkoutPayload('card');
        $payload['shipping_address_line2'] = 'Unit 4';

        $this->postJson(route('shop.checkout.place'), $payload)->assertOk();

        $order = Order::query()->firstOrFail();
        $billing = session('checkout.card.billing.'.$order->order_number);

        $this->assertSame([
            'streetAddress' => '123 Test St, Unit 4',
            'city' => 'Austin',
            'postalCode' => '78701',
            'countryCode' => 'US',
        ], $billing['address']);
        $this->assertArrayNotHasKey('addressLine1', $billing['address']);
        $this->assertArrayNotHasKey('adminArea2', $billing['address']);
    }

    public function test_paypal_checkout_can_create_an_inline_order(): void
    {
        $this->enablePayments();
        $this->addProductToCart();
        $this->fakeInitiation('PAYPAL-INLINE-1');

        $this->postJson(route('shop.checkout.place'), $this->checkoutPayload('paypal'))
            ->assertOk()
            ->assertJsonPath('paypal_order_id', 'PAYPAL-INLINE-1')
            ->assertJsonStructure(['order_number', 'confirm_url', 'amount', 'currency', 'country']);

        $transaction = PaymentTransaction::query()->firstOrFail();
        $this->assertSame('paypal', $transaction->payment_method);
        $this->assertSame('PAYPAL-INLINE-1', $transaction->gateway_transaction_id);
    }

    public function test_apple_pay_checkout_can_create_an_inline_order(): void
    {
        $this->enablePayments();
        $this->addProductToCart();
        $this->fakeInitiation('PAYPAL-APPLE-INLINE-1');

        $this->postJson(route('shop.checkout.place'), $this->checkoutPayload('apple_pay'))
            ->assertOk()
            ->assertJsonPath('paypal_order_id', 'PAYPAL-APPLE-INLINE-1')
            ->assertJsonStructure(['order_number', 'confirm_url', 'amount', 'currency', 'country']);

        $transaction = PaymentTransaction::query()->firstOrFail();
        $this->assertSame('apple_pay', $transaction->payment_method);
        $this->assertSame('PAYPAL-APPLE-INLINE-1', $transaction->gateway_transaction_id);
    }

    public function test_express_apple_pay_creates_an_apple_pay_transaction(): void
    {
        $this->enablePayments();
        $this->addProductToCart();
        $this->fakeInitiation('PAYPAL-APPLE-EXPRESS-1');

        $this->postJson(route('shop.checkout.express.paypal'), array_merge(
            $this->checkoutPayload('apple_pay'),
            ['payment_method' => 'apple_pay'],
        ))
            ->assertOk()
            ->assertJsonPath('paypal_order_id', 'PAYPAL-APPLE-EXPRESS-1')
            ->assertJsonStructure(['confirm_url', 'amount', 'currency', 'country']);

        $this->assertSame('apple_pay', PaymentTransaction::query()->firstOrFail()->payment_method);
        $this->assertSame('apple_pay', session('checkout.method'));
    }

    public function test_express_apple_pay_rejects_requests_when_it_is_disabled(): void
    {
        $this->enablePayments();
        Setting::query()->where('key', 'payment_apple_pay_enabled')->update(['value' => '0']);

        $this->postJson(route('shop.checkout.express.paypal'), ['payment_method' => 'apple_pay'])
            ->assertUnprocessable();
    }

    public function test_card_checkout_creates_paypal_order_and_renders_v6_card_fields(): void
    {
        $this->enablePayments();
        $this->addProductToCart();
        $this->fakeInitiation('PAYPAL-CARD-1');

        $this->post(route('shop.checkout.place'), $this->checkoutPayload('card'))->assertRedirect();

        $order = Order::query()->firstOrFail();
        $transaction = PaymentTransaction::query()->firstOrFail();
        $this->assertSame('pending', $order->status);
        $this->assertSame('card', $transaction->payment_method);
        $this->assertSame('PAYPAL-CARD-1', $transaction->gateway_transaction_id);

        $this->get(route('shop.checkout.processing', $order->order_number))
            ->assertOk()
            ->assertSee('card-fields', false)
            ->assertSee('/web-sdk/v6/core', false)
            ->assertSee('paypal-client-id', false)
            ->assertDontSee('data-client-token=', false)
            ->assertSee('checkout:loading', false)
            ->assertSee('Processing your card payment...')
            ->assertSee('paypalErrorMessage', false)
            ->assertSee('Advanced Card Payments is not eligible for this merchant, buyer, currency, or browser context.')
            ->assertDontSee('js.stripe.com', false);
    }

    public function test_card_confirm_captures_matching_paypal_order(): void
    {
        $this->enablePayments();
        $order = $this->orderWithTransaction('card', 'PAYPAL-CARD-1');
        $this->fakeConfirmation('PAYPAL-CARD-1', 'APPROVED', 'CAPTURE-CARD-1');

        $request = Request::create('/', 'POST', ['paypal_order_id' => 'PAYPAL-CARD-1']);
        $this->assertTrue(app(CardGateway::class)->confirm($order, $request));
        $this->assertSame('CAPTURE-CARD-1', $request->input('gateway_transaction_id'));
    }

    public function test_card_confirm_accepts_already_completed_order(): void
    {
        $this->enablePayments();
        $order = $this->orderWithTransaction('card', 'PAYPAL-CARD-1');
        $this->fakeOrderSummary('PAYPAL-CARD-1', 'COMPLETED', '120.50', 'USD', 'CAPTURE-CARD-1');

        $request = Request::create('/', 'POST', ['paypal_order_id' => 'PAYPAL-CARD-1']);
        $this->assertTrue(app(CardGateway::class)->confirm($order, $request));
        $this->assertSame('CAPTURE-CARD-1', $request->input('gateway_transaction_id'));
    }

    public function test_card_confirm_rejects_amount_mismatch(): void
    {
        $this->enablePayments();
        $order = $this->orderWithTransaction('card', 'PAYPAL-CARD-1');
        $this->fakeOrderSummary('PAYPAL-CARD-1', 'COMPLETED', '999.00', 'USD', 'CAPTURE-CARD-1');
        $request = Request::create('/', 'POST', ['paypal_order_id' => 'PAYPAL-CARD-1']);

        $this->assertFalse(app(CardGateway::class)->confirm($order, $request));
    }

    public function test_apple_pay_uses_paypal_sdk_and_capture_flow(): void
    {
        $this->enablePayments();
        $order = $this->orderWithTransaction('apple_pay', 'PAYPAL-APPLE-1');
        $this->fakeOrderSummary('PAYPAL-APPLE-1', 'CREATED');

        $result = app(ApplePayGateway::class)->initiate($order, Request::create('/', 'GET'));
        $this->assertSame('https://www.sandbox.paypal.com/web-sdk/v6/core', $result->viewData['webSdkUrl']);
        $this->assertSame('paypal-client-id', $result->viewData['clientId']);
        $this->assertSame('PAYPAL-APPLE-1', $result->viewData['paypalOrderId']);

        $this->fakeConfirmation('PAYPAL-APPLE-1', 'APPROVED', 'CAPTURE-APPLE-1');
        $request = Request::create('/', 'POST', ['paypal_order_id' => 'PAYPAL-APPLE-1']);
        $this->assertTrue(app(ApplePayGateway::class)->confirm($order, $request));
        $this->assertSame('CAPTURE-APPLE-1', $request->input('gateway_transaction_id'));
    }

    private function enablePayments(): void
    {
        foreach ([
            'payment_card_enabled' => '1',
            'payment_paypal_enabled' => '1',
            'payment_apple_pay_enabled' => '1',
            'payment_paypal_client_id' => 'paypal-client-id',
            'payment_paypal_client_secret' => 'paypal-client-secret',
            'payment_paypal_sandbox' => '1',
        ] as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    private function fakeInitiation(string $paypalOrderId): void
    {
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'access-token'], 200),
            'https://api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response(['id' => $paypalOrderId, 'status' => 'CREATED'], 201),
            'https://api-m.sandbox.paypal.com/v1/identity/generate-token' => Http::response(['client_token' => 'client-token-test'], 200),
        ]);
    }

    private function fakeOrderSummary(
        string $paypalOrderId,
        string $status,
        string $amount = '120.50',
        string $currency = 'USD',
        ?string $captureId = null,
    ): void {
        $unit = ['amount' => ['value' => $amount, 'currency_code' => $currency]];
        if ($captureId !== null) {
            $unit['payments'] = ['captures' => [['id' => $captureId, 'status' => 'COMPLETED']]];
        }

        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'access-token'], 200),
            'https://api-m.sandbox.paypal.com/v2/checkout/orders/'.$paypalOrderId => Http::response([
                'id' => $paypalOrderId,
                'status' => $status,
                'purchase_units' => [$unit],
            ], 200),
        ]);
    }

    private function fakeConfirmation(string $paypalOrderId, string $status, string $captureId): void
    {
        Http::fake(function ($request) use ($paypalOrderId, $status, $captureId) {
            if (str_ends_with($request->url(), '/v1/oauth2/token')) {
                return Http::response(['access_token' => 'access-token'], 200);
            }
            if (str_ends_with($request->url(), '/v2/checkout/orders/'.$paypalOrderId)) {
                return Http::response([
                    'id' => $paypalOrderId,
                    'status' => $status,
                    'purchase_units' => [[
                        'amount' => ['value' => '120.50', 'currency_code' => 'USD'],
                    ]],
                ], 200);
            }
            if (str_ends_with($request->url(), '/v2/checkout/orders/'.$paypalOrderId.'/capture')) {
                return Http::response([
                    'id' => $paypalOrderId,
                    'status' => 'COMPLETED',
                    'purchase_units' => [[
                        'payments' => ['captures' => [['id' => $captureId, 'status' => 'COMPLETED']]],
                    ]],
                ], 201);
            }

            return Http::response([], 404);
        });
    }

    private function addProductToCart(): void
    {
        $category = Category::query()->create(['name' => 'Bracelets', 'slug' => 'bracelets']);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Card Test Bracelet',
            'slug' => 'card-test-bracelet',
            'price_usd' => 120.50,
            'stock' => 5,
            'is_active' => true,
        ]);
        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'price_usd' => 120.50,
            'stock' => 5,
            'is_default' => true,
            'is_active' => true,
        ]);
        app(CartService::class)->add($variant->id, 1);
    }

    /** @return array<string, string> */
    private function checkoutPayload(string $method): array
    {
        return [
            'payment_method' => $method,
            'customer_email' => 'customer@example.com',
            'shipping_country' => 'US',
            'shipping_first_name' => 'Jane',
            'shipping_last_name' => 'Customer',
            'shipping_address_line1' => '123 Test St',
            'shipping_address_line2' => '',
            'shipping_city' => 'Austin',
            'shipping_postcode' => '78701',
            'shipping_phone' => '5551234567',
            'shipping_method' => 'standard',
            'card_billing_same_as_shipping' => '1',
        ];
    }

    private function orderWithTransaction(string $method, string $paypalOrderId): Order
    {
        $order = Order::query()->create([
            'order_number' => 'ORD-'.strtoupper($method),
            'customer_email' => 'customer@example.com',
            'customer_name' => 'Jane Customer',
            'shipping_address' => 'Jane Customer',
            'currency_code' => 'USD',
            'subtotal_usd' => 120.50,
            'discount_usd' => 0,
            'shipping_usd' => 0,
            'tax_usd' => 0,
            'total_display' => 120.50,
            'status' => 'pending',
        ]);

        PaymentTransaction::query()->create([
            'order_id' => $order->id,
            'payment_method' => $method,
            'gateway_transaction_id' => $paypalOrderId,
            'amount' => 120.50,
            'currency_code' => 'USD',
            'status' => 'pending',
        ]);

        return $order;
    }
}
