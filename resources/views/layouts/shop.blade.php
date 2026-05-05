<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? ($siteSettings['site_name'] ?? config('app.name')) }}</title>
    @if(!empty($metaDescription))
        <meta name="description" content="{{ $metaDescription }}">
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
                    <img src="{{ $siteSettings['site_logo'] }}" alt="{{ $siteSettings['site_name'] ?? config('app.name') }}" style="height:40px;width:auto;display:block;">
                @else
                    {{ $siteSettings['site_name'] ?? config('app.name') }}
                @endif
            </a>
            <button type="button" class="nav-toggle" aria-expanded="false" aria-controls="site-nav" data-nav-toggle>Menu</button>
            <nav class="site-nav" id="site-nav" data-nav-panel>
                <ul class="site-nav__list">
                    <li><a href="{{ route('shop.home') }}">Home</a></li>
                    <li><a href="{{ route('shop.catalog') }}">Catalog</a></li>
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
                @auth
                    <span class="site-user" title="{{ Auth::user()->email }}">{{ Auth::user()->name }}</span>
                    <form method="post" action="{{ route('shop.logout') }}" class="site-logout-form">
                        @csrf
                        <button type="submit" class="site-signout">Sign out</button>
                    </form>
                @else
                    <a class="cart-link" href="{{ route('login') }}">Sign in</a>
                @endauth
                <a class="cart-link" href="{{ route('shop.cart') }}">Cart</a>
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

    <footer class="site-footer">
        <div class="site-footer__grid">
            <div>
                <strong>{{ $siteSettings['site_name'] ?? config('app.name') }}</strong>
                <p>Gemstone jewelry &amp; feng shui pieces for balance and intention — crafted for US customers.</p>
            </div>
            <div>
                <h3 class="site-footer__heading">Shop</h3>
                <ul class="site-footer__links">
                    <li><a href="{{ route('shop.catalog') }}">Catalog</a></li>
                    <li><a href="{{ route('shop.cart') }}">Cart</a></li>
                </ul>
            </div>
            <div>
                <h3 class="site-footer__heading">Policies</h3>
                <ul class="site-footer__links">
                    <li><a href="{{ route('shop.policy.security') }}">Security policy</a></li>
                    <li><a href="{{ route('shop.policy.privacy') }}">Privacy policy</a></li>
                    <li><a href="{{ route('shop.policy.retail') }}">Retail policy</a></li>
                    <li><a href="{{ route('shop.contact') }}">Contact</a></li>
                </ul>
            </div>
        </div>
        <p class="site-footer__copy">&copy; {{ date('Y') }} {{ $siteSettings['site_name'] ?? config('app.name') }}. All rights reserved.</p>
    </footer>
    <script src="{{ asset('assets/js/shop.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
