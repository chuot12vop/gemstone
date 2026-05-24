<form method="post"
      action="{{ route('shop.checkout.confirm', ['order_number' => $order->order_number]) }}"
      class="gateway-pane__proof-form"
      enctype="multipart/form-data">
    @csrf
    <h3 class="gateway-pane__subtitle">Upload payment proof</h3>
    <p class="gateway-pane__hint">Take a screenshot showing the completed transfer (amount, recipient, and date).</p>

    @if($errors->any())
        <p class="banner banner--err">{{ $errors->first() }}</p>
    @endif

    <label class="gateway-pane__file">
        <span class="gateway-pane__file-label">Transfer screenshot <span aria-hidden="true">*</span></span>
        <input type="file" name="payment_proof" accept="image/jpeg,image/png,image/webp" capture="environment" required>
    </label>

    <label class="gateway-pane__field">
        <span>Reference / transaction ID (optional)</span>
        <input type="text" name="transfer_reference" value="{{ old('transfer_reference') }}" maxlength="120" placeholder="e.g. last 4 digits or confirmation #">
    </label>

    <div class="gateway-pane__cta">
        <button class="btn btn--primary" type="submit">Submit payment proof</button>
    </div>
</form>
