<?php

namespace App\Services\Payment\Data;

/**
 * Plain-data result returned by {@see \App\Services\Payment\Contracts\PaymentGateway::initiate()}.
 *
 * The checkout controller decides how to react based on {@see self::$type}:
 *   - "redirect"  : send the customer to {@see self::$redirectUrl}
 *   - "view"      : render the gateway's `processingView()` on /checkout/processing
 *   - "completed" : payment was settled inline; go straight to confirmation
 */
final class PaymentInitiationResult
{
    public const TYPE_REDIRECT = 'redirect';
    public const TYPE_VIEW = 'view';
    public const TYPE_COMPLETED = 'completed';

    /** @var array<string, mixed> */
    public array $viewData;

    public string $type;

    public ?string $redirectUrl;

    public ?string $gatewayTransactionId;

    public ?string $notes;

    /**
     * @param  array<string, mixed>  $viewData
     */
    private function __construct(
        string $type,
        ?string $redirectUrl = null,
        array $viewData = [],
        ?string $gatewayTransactionId = null,
        ?string $notes = null
    ) {
        $this->type = $type;
        $this->redirectUrl = $redirectUrl;
        $this->viewData = $viewData;
        $this->gatewayTransactionId = $gatewayTransactionId;
        $this->notes = $notes;
    }

    /**
     * @param  array<string, mixed>  $viewData
     */
    public static function view(array $viewData = [], ?string $notes = null): self
    {
        return new self(type: self::TYPE_VIEW, viewData: $viewData, notes: $notes);
    }

    public static function redirect(string $url, ?string $notes = null): self
    {
        return new self(type: self::TYPE_REDIRECT, redirectUrl: $url, notes: $notes);
    }

    public static function completed(?string $gatewayTransactionId = null, ?string $notes = null): self
    {
        return new self(
            type: self::TYPE_COMPLETED,
            gatewayTransactionId: $gatewayTransactionId,
            notes: $notes,
        );
    }
}
