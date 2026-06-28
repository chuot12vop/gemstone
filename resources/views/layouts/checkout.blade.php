<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? ($siteSettings['site_name'] ?? config('app.name')) }}</title>
    @if(!empty($metaDescription))
        <meta name="description" content="{{ $metaDescription }}">
    @endif
    @if(!empty($siteSettings['site_logo']))
        <link rel="icon" href="{{ $siteSettings['site_logo'] }}" type="image/png">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500&family=Source+Sans+3:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/shop.css') }}">
    @foreach(\App\Support\CustomThemeStylesheet::LOAD_ORDER as $customThemeViewport)
        @php($customThemeConfig = \App\Support\CustomThemeStylesheet::VIEWPORTS[$customThemeViewport])
        @if($customThemeUrl = \App\Support\CustomThemeStylesheet::versionedUrl($customThemeViewport))
            <link rel="stylesheet" href="{{ $customThemeUrl }}"
                  @if($customThemeConfig['media']) media="{{ $customThemeConfig['media'] }}" @endif
                  data-custom-theme-stylesheet="{{ $customThemeViewport }}">
        @endif
    @endforeach
    <link rel="stylesheet" href="{{ asset('assets/css/file-upload.css') }}">
    @stack('head')
</head>
<body class="site checkout-page">
    <a class="skip-link" href="#main">Skip to content</a>
    <header class="checkout-header">
        <div class="checkout-header__inner">
            <a class="logo checkout-header__logo logo--missoma {{ !($siteSettings['show_site_name'] ?? true) ? 'logo--name-hidden' : '' }} {{ ($siteSettings['hide_site_name_mobile'] ?? false) ? 'logo--hide-name-mobile' : '' }}" href="{{ route('shop.home') }}">
                @if(($siteSettings['show_site_logo'] ?? true) && !empty($siteSettings['site_logo']))
                    <span class="logo__frame">
                        <img class="logo__img" src="{{ $siteSettings['site_logo'] }}" alt="{{ $siteSettings['site_name'] ?? config('app.name') }}">
                    </span>
                @endif
                @if($siteSettings['show_site_name'] ?? true)
                    <span class="logo__name">{{ $siteSettings['site_name'] ?? config('app.name') }}</span>
                @endif
            </a>
            <div class="checkout-header__actions">
                <form class="currency-form currency-form--checkout" method="post" action="{{ route('shop.currency') }}">
                    @csrf
                    <label class="sr-only" for="checkout-currency">Currency</label>
                    <select name="currency" id="checkout-currency" onchange="this.form.submit()">
                        @foreach($currency->activeCurrencies() as $c)
                            <option value="{{ $c['code'] }}" @selected($currency->currentCode() === $c['code'])>
                                {{ $c['code'] }} ({{ $c['symbol'] }})
                            </option>
                        @endforeach
                    </select>
                </form>
                <a class="checkout-header__cart" href="{{ route('shop.cart') }}">&larr; Back to cart</a>
            </div>
        </div>
    </header>

    @include('shop.partials.flash-toasts')

    <main id="main" class="checkout-main @yield('mainClass')">
        @yield('content')
    </main>

    <footer class="checkout-footer">
        <ul class="checkout-footer__links">
            <li><a href="{{ route('shop.policy.security') }}">Security</a></li>
            <li><a href="{{ route('shop.policy.privacy') }}">Privacy</a></li>
            <li><a href="{{ route('shop.policy.return') }}">Returns</a></li>
            <li><a href="{{ route('shop.policy.terms') }}">Terms</a></li>
            <li><a href="{{ route('shop.contact') }}">Contact</a></li>
        </ul>
        <div class="checkout-footer__payments">
            @include('shop.partials.payment-icons')
        </div>
        <p class="checkout-footer__copy">&copy; {{ date('Y') }} {{ $siteSettings['site_name'] ?? config('app.name') }}</p>
    </footer>

    <div class="checkout-loading" data-checkout-loading role="status" aria-live="polite" aria-atomic="true" aria-hidden="true" hidden>
        <div class="checkout-loading__panel">
            <span class="checkout-loading__spinner" aria-hidden="true"></span>
            <p class="checkout-loading__message" data-checkout-loading-message>Processing your payment...</p>
            <p class="checkout-loading__hint">Please do not close or refresh this page.</p>
        </div>
    </div>

    <script src="{{ asset('assets/js/file-upload.js') }}" defer></script>
    <script src="{{ asset('assets/js/paypal-checkout.js') }}"></script>
    <script src="{{ asset('assets/js/shop.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
