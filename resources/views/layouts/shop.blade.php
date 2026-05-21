<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
    @stack('head')
</head>
<body class="site">
    <a class="skip-link" href="#main">Skip to content</a>
    <header class="site-header">
        <div class="site-header__inner">
            <a class="logo" href="{{ route('shop.home') }}">
                @if(!empty($siteSettings['site_logo']))
                    <img class="logo__img" src="{{ $siteSettings['site_logo'] }}" alt="">
                @endif
                <span class="logo__name">{{ $siteSettings['site_name'] ?? config('app.name') }}</span>
            </a>
            @php($cartCount = (int) array_sum((array) session('cart', [])))
            <div class="site-header__actions">
                <a class="cart-link cart-link--icon" href="{{ route('shop.cart') }}" aria-label="Cart ({{ $cartCount }} items)">
                    <svg class="cart-link__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h8.7a2 2 0 0 0 1.9-1.4l1.7-5.6a1 1 0 0 0-.9-1.3H8.2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="10" cy="19" r="1.6" fill="currentColor"/>
                        <circle cx="18" cy="19" r="1.6" fill="currentColor"/>
                    </svg>
                    <span class="cart-link__count" aria-hidden="true">{{ $cartCount }}</span>
                </a>
                <button type="button" class="nav-toggle" aria-expanded="false" aria-controls="site-nav" data-nav-toggle>Menu</button>
            </div>
            <nav class="site-nav" id="site-nav" data-nav-panel>
                <ul class="site-nav__list">
                    <li><a href="{{ route('shop.home') }}">Home</a></li>
                    <li class="site-nav__item site-nav__item--mega" data-nav-mega>
                        <a href="{{ route('shop.catalog') }}" class="site-nav__mega-trigger" data-catalog-trigger aria-expanded="false" aria-controls="catalog-mega-panel" id="catalog-mega-trigger">Collections</a>
                        <div class="catalog-mega" id="catalog-mega-panel" role="region" aria-labelledby="catalog-mega-trigger" data-catalog-mega-panel>
                            <div class="catalog-mega__inner">
                                <p class="catalog-mega__lede"><a href="{{ route('shop.catalog') }}">Collections</a></p>
                                @if($catalogNavCategories->isEmpty())
                                    <p class="catalog-mega__empty">No categories yet.</p>
                                @else
                                    <div class="catalog-mega__grid">
                                        @foreach($catalogNavCategories as $cat)
                                            <div class="catalog-mega__col">
                                                <a class="catalog-mega__cat" href="{{ route('shop.catalog.category', $cat) }}">{{ $cat->name }}</a>
                                                @if($cat->products->isEmpty())
                                                    <p class="catalog-mega__empty catalog-mega__empty--inline">No products in this category.</p>
                                                @else
                                                    <ul class="catalog-mega__products">
                                                        @foreach($cat->products as $product)
                                                            <li><a href="{{ route('shop.product', $product) }}">{{ $product->name }}</a></li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </li>
                    <li><a href="{{ route('shop.news.index') }}">News</a></li>
                    <li><a href="{{ route('shop.about') }}">About</a></li>
                    <li><a href="{{ route('shop.contact') }}">Contact</a></li>
                </ul>
                <form class="currency-form" method="post" action="{{ route('shop.currency') }}">
                    @csrf
                    <label class="sr-only" for="currency">Currency</label>
                    <select name="currency" id="currency" onchange="this.form.submit()">
                        @foreach($currency->activeCurrencies() as $c)
                            <option value="{{ $c['code'] }}" @selected($currency->currentCode() === $c['code'])>
                                {{ $c['code'] }} ({{ $c['symbol'] }})
                            </option>
                        @endforeach
                    </select>
                </form>
                <a class="btn btn--small btn--header-buy" href="{{ route('shop.products.index') }}">Buy now</a>
                @auth
                    <a class="cart-link" href="{{ route('shop.account.index') }}" title="My account">{{ Auth::user()->name }}</a>
                    <form method="post" action="{{ route('shop.logout') }}" class="site-logout-form">
                        @csrf
                        <button type="submit" class="site-signout">Sign out</button>
                    </form>
                @else
                    <a class="cart-link" href="{{ route('login') }}">Sign in</a>
                @endauth
            </nav>
        </div>
    </header>

    @if(session('success'))
        <p class="banner banner--ok" role="status">{{ session('success') }}</p>
    @endif
    @if(session('error'))
        <p class="banner banner--err" role="alert">{{ session('error') }}</p>
    @endif

    <main id="main" class="site-main @yield('mainClass')">
        @yield('content')
    </main>

    @include('shop.partials.whatsapp-float')

    <footer class="site-footer{{ !empty($shopFront['footer_background']) ? ' site-footer--has-bg' : '' }}"
            @if(!empty($shopFront['footer_background'])) style="--footer-bg: url('{{ $shopFront['footer_background'] }}');" @endif>
        <div class="site-footer__grid">
            <div>
                <strong>{{ $siteSettings['site_name'] ?? config('app.name') }}</strong>
                <p>Gemstone jewelry &amp; feng shui pieces for balance and intention — crafted for US customers.</p>
            </div>
            <div>
                <h3 class="site-footer__heading">Shop</h3>
                <ul class="site-footer__links">
                    <li><a href="{{ route('shop.products.index') }}">Catalog</a></li>
                    <li><a href="{{ route('shop.cart') }}">Cart</a></li>
                </ul>
            </div>
            <div>
                <h3 class="site-footer__heading">Policies</h3>
                <ul class="site-footer__links">
                    <li><a href="{{ route('shop.policy.security') }}">Security policy</a></li>
                    <li><a href="{{ route('shop.policy.privacy') }}">Privacy policy</a></li>
                    <li><a href="{{ route('shop.policy.return') }}">Return policy</a></li>
                    <li><a href="{{ route('shop.policy.terms') }}">Terms of service</a></li>
                    <li><a href="{{ route('shop.contact') }}">Contact</a></li>
                </ul>
            </div>
        </div>
        <div class="site-footer__payments">
            @include('shop.partials.payment-icons')
        </div>
        <p class="site-footer__copy">&copy; {{ date('Y') }} {{ $siteSettings['site_name'] ?? config('app.name') }}. All rights reserved.</p>
    </footer>
    <script src="{{ asset('assets/js/shop.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
