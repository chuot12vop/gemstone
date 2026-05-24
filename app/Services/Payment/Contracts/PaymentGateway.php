<?php

namespace App\Services\Payment\Contracts;

use App\Models\Order;
use App\Services\Payment\Data\PaymentInitiationResult;
use Illuminate\Http\Request;

/**
 * Strategy contract for a single payment method.
 *
 * Adding a new method = create a class implementing this interface and tag it in
 * {@see \App\Providers\PaymentServiceProvider}. The checkout flow stays untouched.
 */
interface PaymentGateway
{
    /** Stable machine code persisted in `payment_transactions.payment_method`. */
    public function code(): string;

    /** Human-readable label shown in the method picker. */
    public function label(): string;

    /** Short helper text rendered under the label. */
    public function description(): string;

    /** Inline SVG (or HTML) used as the method icon. Empty string is allowed. */
    public function iconHtml(): string;

    /** Whether the method is currently enabled in the admin payment settings. */
    public function isEnabled(): bool;

    /**
     * Validation rules merged into the customer-info form for this method.
     *
     * @return array<string, string|array<int, string>>
     */
    public function validationRules(): array;

    /**
     * Optional Blade partial rendered inside the customer-info form to collect
     * extra fields (e.g. PayPal email, WhatsApp phone confirmation, …).
     */
    public function customerFieldsView(): ?string;

    /**
     * Initiate the payment after the order has been created.
     *
     * Implementations should NOT mark the transaction as paid here — they should
     * return a {@see PaymentInitiationResult} describing what the customer needs
     * to do next (redirect, view, or confirm).
     */
    public function initiate(Order $order, Request $request): PaymentInitiationResult;

    /**
     * Blade partial used on `/checkout/processing/{order}` to walk the customer
     * through the gateway-specific flow (PayPal button, WhatsApp deep-link, …).
     */
    public function processingView(): string;

    /**
     * Customer-side confirmation handler (the "I have paid" / gateway return
     * postback). Returning true marks the transaction as `paid` and the order
     * as `paid` on the calling controller's side when {@see marksOrderPaidOnConfirm}
     * is true.
     */
    public function confirm(Order $order, Request $request): bool;

    /**
     * When false, {@see confirm} may succeed (e.g. proof uploaded) without
     * marking the order paid — staff verifies manually.
     */
    public function marksOrderPaidOnConfirm(): bool;
}
