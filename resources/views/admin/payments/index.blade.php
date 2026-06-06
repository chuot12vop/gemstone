@extends('layouts.admin')

@section('module-meta')
    {{ $transactions->count() }} recent transactions
@endsection

@section('content')
@if($errors->any())
    <div class="admin-banner admin-banner--err" role="alert" style="margin-bottom:12px;">
        {{ $errors->first() }}
    </div>
@endif

<div style="display:flex;gap:10px;margin-bottom:16px;">
    <a href="{{ route('admin.payments.index', ['tab' => 'history']) }}"
       class="btn-admin {{ ($tab ?? 'history') === 'history' ? 'btn-admin--primary' : '' }}">
        Transaction history
    </a>
    <a href="{{ route('admin.payments.index', ['tab' => 'settings']) }}"
       class="btn-admin {{ ($tab ?? 'history') === 'settings' ? 'btn-admin--primary' : '' }}">
        Payment settings
    </a>
</div>

@if(($tab ?? 'history') === 'settings')
    <h2 class="admin-h2">Payment method settings</h2>
    <form class="stack-form" method="post" action="{{ route('admin.payments.settings') }}" enctype="multipart/form-data">
        @csrf
        <fieldset class="form-fieldset">
            <legend>PayPal</legend>
            <label class="switch-field">
                <span class="switch-field__label">Enable method</span>
                <span class="switch">
                    <input type="checkbox" name="paypal_enabled" value="1" @checked(old('paypal_enabled', ($settings['payment_paypal_enabled'] ?? '0') === '1'))>
                    <span class="switch__slider" aria-hidden="true"></span>
                </span>
            </label>
            <label>
                Merchant email
                <input type="email" name="paypal_merchant_email" value="{{ old('paypal_merchant_email', $settings['payment_paypal_merchant_email'] ?? '') }}">
            </label>
            <label>
                REST API Client ID
                <input type="text" name="paypal_client_id" value="{{ old('paypal_client_id', $settings['payment_paypal_client_id'] ?? '') }}" autocomplete="off" placeholder="From PayPal Developer Dashboard → Apps">
            </label>
            <label>
                REST API Client Secret
                <input type="password" name="paypal_client_secret" value="" autocomplete="new-password" placeholder="{{ ($hasPaypalSecret ?? false) ? 'Saved — leave blank to keep' : 'Required for live checkout' }}">
            </label>
            <label class="switch-field">
                <span class="switch-field__label">Sandbox (test mode)</span>
                <span class="switch">
                    <input type="checkbox" name="paypal_sandbox" value="1" @checked(old('paypal_sandbox', ($settings['payment_paypal_sandbox'] ?? '1') === '1'))>
                    <span class="switch__slider" aria-hidden="true"></span>
                </span>
            </label>
            <p class="muted" style="margin:0;">Create an app at <a href="https://developer.paypal.com/dashboard/applications/live" target="_blank" rel="noopener">developer.paypal.com</a>. Use Sandbox credentials while testing; turn off Sandbox and switch to Live credentials before accepting real payments.</p>
        </fieldset>

        <fieldset class="form-fieldset">
            <legend>Whatsapp</legend>
            <label class="switch-field">
                <span class="switch-field__label">Enable method</span>
                <span class="switch">
                    <input type="checkbox" name="whatsapp_enabled" value="1" @checked(old('whatsapp_enabled', ($settings['payment_whatsapp_enabled'] ?? '0') === '1'))>
                    <span class="switch__slider" aria-hidden="true"></span>
                </span>
            </label>
            <label>
                Phone number
                <input type="text" name="whatsapp_phone" value="{{ old('whatsapp_phone', $settings['payment_whatsapp_phone'] ?? '') }}" placeholder="+849xxxxxxxx">
            </label>
            <label>
                Message template
                <textarea name="whatsapp_message_template" rows="4">{{ old('whatsapp_message_template', $settings['payment_whatsapp_message_template'] ?? 'Hello, I would like to pay for order #{order_number}') }}</textarea>
            </label>
        </fieldset>

        <fieldset class="form-fieldset">
            <legend>ApplePay</legend>
            <label class="switch-field">
                <span class="switch-field__label">Enable method</span>
                <span class="switch">
                    <input type="checkbox" name="apple_pay_enabled" value="1" @checked(old('apple_pay_enabled', ($settings['payment_apple_pay_enabled'] ?? '0') === '1'))>
                    <span class="switch__slider" aria-hidden="true"></span>
                </span>
            </label>
            <label>
                Merchant ID
                <input type="text" name="apple_pay_merchant_id" value="{{ old('apple_pay_merchant_id', $settings['payment_apple_pay_merchant_id'] ?? '') }}">
            </label>
            <label>
                Verified domain
                <input type="text" name="apple_pay_domain" value="{{ old('apple_pay_domain', $settings['payment_apple_pay_domain'] ?? '') }}" placeholder="example.com">
            </label>
        </fieldset>

        <fieldset class="form-fieldset">
            <legend>Venmo</legend>
            <p class="muted" style="margin:0 0 8px;">QR is generated per order (amount + order note). Customer uploads transfer screenshot after paying.</p>
            <label class="switch-field">
                <span class="switch-field__label">Enable method</span>
                <span class="switch">
                    <input type="checkbox" name="venmo_enabled" value="1" @checked(old('venmo_enabled', ($settings['payment_venmo_enabled'] ?? '0') === '1'))>
                    <span class="switch__slider" aria-hidden="true"></span>
                </span>
            </label>
            <label>
                Venmo username
                <input type="text" name="venmo_username" value="{{ old('venmo_username', $settings['payment_venmo_username'] ?? '') }}" placeholder="YourVenmoHandle">
            </label>
        </fieldset>

        <fieldset class="form-fieldset">
            <legend>Cash App</legend>
            <p class="muted" style="margin:0 0 8px;">Upload a static QR from your Cash App profile. Customers pay the order total and upload proof.</p>
            <label class="switch-field">
                <span class="switch-field__label">Enable method</span>
                <span class="switch">
                    <input type="checkbox" name="cashapp_enabled" value="1" @checked(old('cashapp_enabled', ($settings['payment_cashapp_enabled'] ?? '0') === '1'))>
                    <span class="switch__slider" aria-hidden="true"></span>
                </span>
            </label>
            <label>
                $Cashtag
                <input type="text" name="cashapp_cashtag" value="{{ old('cashapp_cashtag', $settings['payment_cashapp_cashtag'] ?? '') }}" placeholder="YourCashtag">
            </label>
            @include('partials.file-upload', [
                'name' => 'cashapp_qr',
                'label' => 'QR image',
                'previewUrl' => !empty($settings['payment_cashapp_qr_image']) ? $settings['payment_cashapp_qr_image'] : null,
                'previewWidth' => 140,
                'previewHeight' => 140,
            ])
        </fieldset>

        <fieldset class="form-fieldset">
            <legend>Zelle</legend>
            <p class="muted" style="margin:0 0 8px;">Upload a static Zelle QR from your bank. Customers pay the order total and upload proof.</p>
            <label class="switch-field">
                <span class="switch-field__label">Enable method</span>
                <span class="switch">
                    <input type="checkbox" name="zelle_enabled" value="1" @checked(old('zelle_enabled', ($settings['payment_zelle_enabled'] ?? '0') === '1'))>
                    <span class="switch__slider" aria-hidden="true"></span>
                </span>
            </label>
            <label>
                Payee label (shown to customer)
                <input type="text" name="zelle_payee_label" value="{{ old('zelle_payee_label', $settings['payment_zelle_payee_label'] ?? '') }}" placeholder="Tachi Gem Stone">
            </label>
            @include('partials.file-upload', [
                'name' => 'zelle_qr',
                'label' => 'QR image',
                'previewUrl' => !empty($settings['payment_zelle_qr_image']) ? $settings['payment_zelle_qr_image'] : null,
                'previewWidth' => 140,
                'previewHeight' => 140,
            ])
        </fieldset>

        <div class="form-actions">
            <button class="btn-admin btn-admin--primary" type="submit">Save payment settings</button>
        </div>
    </form>
@else
    <h2 class="admin-h2">Transaction history</h2>
    <form class="stack-form form-inline" method="get" action="{{ route('admin.payments.index') }}" style="margin-bottom:10px;">
        <input type="hidden" name="tab" value="history">
        <label class="form-inline__field">
            Search
            <input type="text" name="q" value="{{ $q }}" placeholder="Order number or Txn ID">
        </label>
        <label class="form-inline__field">
            Method
            <select name="method">
                <option value="">All</option>
                @foreach($methods as $option)
                    <option value="{{ $option }}" @selected($method === $option)>{{ ucfirst(str_replace('_', ' ', $option)) }}</option>
                @endforeach
            </select>
        </label>
        <label class="form-inline__field">
            Status
            <select name="status">
                <option value="">All</option>
                @foreach(\App\Models\PaymentTransaction::STATUSES as $paymentStatus)
                    <option value="{{ $paymentStatus }}" @selected($status === $paymentStatus)>{{ $paymentStatus }}</option>
                @endforeach
            </select>
        </label>
        <button class="btn-admin" type="submit">Filter</button>
    </form>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th>Date</th>
                <th>Order</th>
                <th>Method</th>
                <th>Txn ID</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Notes</th>
                <th>Proof</th>
            </tr>
            </thead>
            <tbody>
            @forelse($transactions as $transaction)
                <tr>
                    <td>{{ $transaction->created_at?->format('Y-m-d H:i') }}</td>
                    <td>
                        @if($transaction->order)
                            <a href="{{ route('admin.orders.show', $transaction->order) }}">{{ $transaction->order->order_number }}</a>
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ ucfirst(str_replace('_', ' ', $transaction->payment_method)) }}</td>
                    <td>{{ $transaction->gateway_transaction_id ?: '-' }}</td>
                    <td>{{ strtoupper($transaction->currency_code) }} {{ number_format((float) $transaction->amount, 2) }}</td>
                    <td><span class="badge badge--{{ $transaction->status }}">{{ $transaction->status }}</span></td>
                    <td>{{ $transaction->notes ?: '-' }}</td>
                    <td>
                        @if($transaction->proof_path)
                            <a href="{{ $transaction->proof_path }}" target="_blank" rel="noopener">View</a>
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="data-table__empty">No payment transactions yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endif
@endsection
