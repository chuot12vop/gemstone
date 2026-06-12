<!DOCTYPE html>
<html lang="en-US"
      data-currency-symbol="{{ $currency->currentSymbol() }}"
      data-currency-rate="{{ $currency->currentRatePerUsd() }}"
      data-currency-code="{{ $currency->currentCode() }}"
      data-cart-bag-url="{{ route('shop.cart.bag-fragment') }}"
      data-cart-add-url="{{ route('shop.cart.add') }}"
      data-cart-update-url="{{ route('shop.cart.update') }}"
      data-cart-remove-url="{{ route('shop.cart.remove') }}">
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
    <link rel="stylesheet" href="{{ asset('assets/css/file-upload.css') }}">
    @stack('head')
</head>
<body class="site">
    <a class="skip-link" href="#main">Skip to content</a>
    <div class="site-top">
    <div class="site-header-shell">
    @include('shop.partials.header-promo-bar')
    <header class="site-header">
        <div class="site-header__inner">
            <div class="site-header__leading">
                <button type="button" class="nav-toggle" aria-expanded="false" aria-controls="site-nav" data-nav-toggle aria-label="Open menu">
                    <span class="nav-toggle__icon" aria-hidden="true">
                        <span class="nav-toggle__bar"></span>
                        <span class="nav-toggle__bar"></span>
                        <span class="nav-toggle__bar"></span>
                    </span>
                </button>
                @include('shop.partials.header-search-dropdown')
            </div>
            <a class="logo site-header__logo logo--missoma {{ !($siteSettings['show_site_name'] ?? true) ? 'logo--name-hidden' : '' }} {{ ($siteSettings['hide_site_name_mobile'] ?? false) ? 'logo--hide-name-mobile' : '' }}" href="{{ route('shop.home') }}">
                @if(($siteSettings['show_site_logo'] ?? true) && !empty($siteSettings['site_logo']))
                    <span class="logo__frame">
                        <img class="logo__img" src="{{ $siteSettings['site_logo'] }}" alt="{{ $siteSettings['site_name'] ?? config('app.name') }}">
                    </span>
                @endif
                @if($siteSettings['show_site_name'] ?? true)
                    <span class="logo__name">{{ $siteSettings['site_name'] ?? config('app.name') }}</span>
                @endif
            </a>
            <div class="site-header__nav-desktop">
                <nav class="site-nav site-nav--desktop" aria-label="Main menu">
                    <div class="site-nav__body">
                        @include('shop.partials.header-nav-menu', ['navPrefix' => 'desktop'])
                    </div>
                </nav>
            </div>
            @php($cartCount = app(\App\Services\CartService::class)->totalQuantity())
            <div class="site-header__actions">
                <a class="header-icon-link cart-link cart-link--icon" href="{{ route('shop.cart') }}" aria-label="Cart ({{ $cartCount }} items)">
                    <svg class="header-icon-link__svg cart-link__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h8.7a2 2 0 0 0 1.9-1.4l1.7-5.6a1 1 0 0 0-.9-1.3H8.2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="10" cy="19" r="1.6" fill="currentColor"/>
                        <circle cx="18" cy="19" r="1.6" fill="currentColor"/>
                    </svg>
                    <span class="cart-link__count" data-header-cart-count aria-hidden="true">{{ $cartCount }}</span>
                </a>
                @auth
                    <a class="header-icon-link" href="{{ route('shop.account.index') }}" aria-label="My account" title="My account">
                        <svg class="header-icon-link__svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <circle cx="12" cy="8" r="3.25" fill="none" stroke="currentColor" stroke-width="1.8"/>
                            <path d="M5.5 19.5c1.4-3.2 4-4.75 6.5-4.75s5.1 1.55 6.5 4.75" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </a>
                @else
                    <a class="header-icon-link" href="{{ route('login') }}" aria-label="Sign in or register" title="Sign in">
                        <svg class="header-icon-link__svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <circle cx="12" cy="8" r="3.25" fill="none" stroke="currentColor" stroke-width="1.8"/>
                            <path d="M5.5 19.5c1.4-3.2 4-4.75 6.5-4.75s5.1 1.55 6.5 4.75" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </a>
                @endauth
            </div>
        </div>
    </header>
    </div>
    </div>

    @include('shop.partials.flash-toasts')

    <main id="main" class="site-main @yield('mainClass')">
        @yield('content')
    </main>

    @include('shop.partials.whatsapp-float')

    <footer class="site-footer{{ !empty($shopFront['footer_background']) ? ' site-footer--has-bg' : '' }}"
            @if(!empty($shopFront['footer_background'])) style="--footer-bg: url('{{ $shopFront['footer_background'] }}');" @endif>
        <div class="site-footer__grid">
            <div class="site-footer__newsletter-col">
                @include('shop.partials.footer-newsletter')
            </div>
            <details class="site-footer__group" data-footer-collapse>
                <summary class="site-footer__heading">Shop</summary>
                <ul class="site-footer__links">
                    <li><a href="{{ route('shop.products.index') }}">Catalog</a></li>
                    <li><a href="{{ route('shop.cart') }}">Cart</a></li>
                </ul>
            </details>
            <details class="site-footer__group" data-footer-collapse>
                <summary class="site-footer__heading">Policies</summary>
                <ul class="site-footer__links">
                    <li><a href="{{ route('shop.policy.security') }}">Security policy</a></li>
                    <li><a href="{{ route('shop.policy.privacy') }}">Privacy policy</a></li>
                    <li><a href="{{ route('shop.policy.return') }}">Return policy</a></li>
                    <li><a href="{{ route('shop.policy.terms') }}">Terms of service</a></li>
                    <li><a href="{{ route('shop.contact') }}">Contact</a></li>
                </ul>
            </details>
        </div>
        <div class="site-footer__payments">
            @include('shop.partials.payment-icons')
        </div>
        <p class="site-footer__copy">&copy; {{ date('Y') }} {{ $siteSettings['site_name'] ?? config('app.name') }}. All rights reserved.</p>
    </footer>
    @include('shop.partials.mobile-nav-drawer')

    <script src="{{ asset('assets/js/file-upload.js') }}" defer></script>
    <script src="{{ asset('assets/js/shop.js') }}" defer></script>
    <script>
    document.addEventListener('click', function (event) {
        var button = event.target.closest('button[data-navigate]');
        if (button && !button.disabled) {
            window.location.assign(button.dataset.navigate);
        }
    });
    </script>
    @stack('scripts')
</body>
</html>
