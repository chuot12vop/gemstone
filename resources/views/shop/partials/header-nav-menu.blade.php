@php
    $navPrefix = $navPrefix ?? 'nav';
    $megaTriggerId = 'catalog-mega-trigger-' . $navPrefix;
    $megaPanelId = 'catalog-mega-panel-' . $navPrefix;
    $newsMegaTriggerId = 'news-mega-trigger-' . $navPrefix;
    $newsMegaPanelId = 'news-mega-panel-' . $navPrefix;
    $currencyId = 'currency-' . $navPrefix;
    $currencies = $currency->activeCurrencies();
    $currentCurrencyCode = $currency->currentCode();
    $currentCurrency = collect($currencies)->firstWhere('code', $currentCurrencyCode) ?? $currencies[0] ?? [
        'code' => 'USD',
        'label' => 'US Dollar',
        'symbol' => '$',
    ];
    $currencyFlags = [
        'USD' => asset('assets/img/flags/us.svg'),
        'EUR' => asset('assets/img/flags/eu.svg'),
        'GBP' => asset('assets/img/flags/gb.svg'),
    ];
    $bestSellersCategory = $catalogNavCategories->firstWhere('slug', 'Best-Sellers');
    $catalogMenuCategories = $catalogNavCategories->reject(fn ($cat) => $cat->slug === 'Best-Sellers');
@endphp
<ul class="site-nav__list">
    <li><a href="{{ route('shop.home') }}">Home</a></li>
    <li>
        <a href="{{ $bestSellersCategory ? route('shop.catalog.category', $bestSellersCategory) : route('shop.products.index') }}">Best Sellers</a>
    </li>
    <li class="site-nav__item site-nav__item--mega" data-nav-mega>
        <button type="button"
                class="site-nav__expand-toggle"
                data-catalog-trigger
                aria-expanded="false"
                aria-controls="{{ $megaPanelId }}"
                aria-label="Toggle collections menu">
            <a href="{{ route('shop.catalog') }}" class="site-nav__mega-trigger" id="{{ $megaTriggerId }}">Collections</a>
            <svg class="site-nav__expand-icon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false">
                <path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
        <div class="catalog-mega"
             id="{{ $megaPanelId }}"
             role="region"
             aria-labelledby="{{ $megaTriggerId }}"
             data-catalog-mega-panel>
            <div class="catalog-mega__inner">
                <p class="catalog-mega__lede"><a href="{{ route('shop.catalog') }}">Collections</a></p>
                @if($catalogMenuCategories->isEmpty())
                    <p class="catalog-mega__empty">No categories yet.</p>
                @else
                    <div class="catalog-mega__list">
                        @foreach($catalogMenuCategories as $cat)
                            @if($cat->products->count() > 1)
                                <details class="catalog-mega__group catalog-mega__group--expandable">
                                    <summary class="catalog-mega__summary">
                                        <span class="catalog-mega__summary-label">{{ $cat->name }}</span>
                                        <svg class="catalog-mega__expand-icon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false">
                                            <path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </summary>
                                    <div class="catalog-mega__panel">
                                        <p class="catalog-mega__panel-head">
                                            <a href="{{ route('shop.catalog.category', $cat) }}">View all {{ $cat->name }}</a>
                                        </p>
                                        <ul class="catalog-mega__products">
                                            @foreach($cat->products as $product)
                                                @php
                                                    $navThumb = $product->thumbnail ?: ($product->image ?: asset('assets/img/placeholder.svg'));
                                                @endphp
                                                <li>
                                                    <a class="catalog-mega__product-link" href="{{ route('shop.product', $product) }}">
                                                        <span class="catalog-mega__product-thumb" aria-hidden="true">
                                                            <img src="{{ $navThumb }}" alt="" width="36" height="36" loading="lazy">
                                                        </span>
                                                        <span class="catalog-mega__product-name">{{ $product->name }}</span>
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </details>
                            @else
                                <div class="catalog-mega__group catalog-mega__group--link">
                                    <a class="catalog-mega__direct" href="{{ route('shop.catalog.category', $cat) }}">{{ $cat->name }}</a>
                                </div>
                            @endif
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
<form class="currency-form" method="post" action="{{ route('shop.currency') }}" data-currency-picker>
    @csrf
    <span class="sr-only" id="{{ $currencyId }}-label">Currency</span>
    <details class="currency-picker">
        <summary class="currency-picker__trigger" aria-labelledby="{{ $currencyId }}-label {{ $currencyId }}-value"> 
            <div class="currency-picker__trigger-inner">
                <span class="currency-picker__flag" aria-hidden="true">
                    @if(isset($currencyFlags[$currentCurrency['code']]))
                        <img src="{{ $currencyFlags[$currentCurrency['code']] }}" alt="">
                    @else
                        <span>{{ substr($currentCurrency['code'], 0, 2) }}</span>
                    @endif
                </span>
                <span class="currency-picker__code" id="{{ $currencyId }}-value">{{ $currentCurrency['code'] }}</span>
                
            </div>
            <svg class="currency-picker__chevron" viewBox="0 0 12 8" aria-hidden="true">
                <path d="M1 1.5 6 6.5l5-5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </summary>
        <div class="currency-picker__menu" role="listbox" aria-labelledby="{{ $currencyId }}-label">
            @foreach($currencies as $c)
                @php($isCurrentCurrency = $currentCurrencyCode === $c['code'])
                <button class="currency-picker__option{{ $isCurrentCurrency ? ' is-selected' : '' }}"
                        type="submit"
                        name="currency"
                        value="{{ $c['code'] }}"
                        role="option"
                        aria-selected="{{ $isCurrentCurrency ? 'true' : 'false' }}">
                    <span class="currency-picker__flag" aria-hidden="true">
                        @if(isset($currencyFlags[$c['code']]))
                            <img src="{{ $currencyFlags[$c['code']] }}" alt="">
                        @else
                            <span>{{ substr($c['code'], 0, 2) }}</span>
                        @endif
                    </span>
                    <span class="currency-picker__option-copy">
                        <strong>{{ $c['code'] }}</strong>
                        <small>{{ $c['label'] }}</small>
                    </span>
                    <span class="currency-picker__symbol">{{ $c['symbol'] }}</span>
                    <svg class="currency-picker__check" viewBox="0 0 16 16" aria-hidden="true">
                        <path d="m3 8.5 3.1 3L13 4.8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            @endforeach
        </div>
    </details>
</form>
<!-- <a class="btn btn--small btn--header-buy" href="{{ route('shop.products.index') }}">Buy now</a> --> <!-- Không có button này -->
<div class="site-nav__account site-nav__account--desktop-dropdown">
    <button type="button"
            class="site-account-menu__trigger"
            aria-label="Account menu">
        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">
            <circle cx="12" cy="8" r="3.2" fill="none" stroke="currentColor" stroke-width="1.9"/>
            <path d="M5.5 19c0-3.3 2.9-5.5 6.5-5.5s6.5 2.2 6.5 5.5" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>
        </svg>
    </button>
    <div class="site-account-menu__panel" role="menu" aria-label="Account links">
        @auth
            <a class="site-account-menu__item" href="{{ route('shop.account.index') }}" role="menuitem">My account</a>
            <form method="post" action="{{ route('shop.logout') }}" class="site-account-menu__form">
                @csrf
                <button type="submit" class="site-account-menu__item site-account-menu__item--button" role="menuitem">Sign out</button>
            </form>
        @else
            <a class="site-account-menu__item" href="{{ route('login') }}" role="menuitem">Sign in</a>
            <a class="site-account-menu__item" href="{{ route('register') }}" role="menuitem">Register</a>
        @endauth
    </div>
</div>
<div class="site-nav__account site-nav__account--mobile">
    @auth
        <a class="cart-link" href="{{ route('shop.account.index') }}" title="My account">{{ Auth::user()->name }}</a>
        <form method="post" action="{{ route('shop.logout') }}" class="site-logout-form">
            @csrf
            <button type="submit" class="site-signout">Sign out</button>
        </form>
    @else
        <span class="site-nav__account-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="18" height="18" focusable="false">
                <circle cx="12" cy="8" r="3.2" fill="none" stroke="currentColor" stroke-width="1.9"/>
                <path d="M5.5 19c0-3.3 2.9-5.5 6.5-5.5s6.5 2.2 6.5 5.5" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>
            </svg>
        </span>
        <a class="cart-link" href="{{ route('login') }}">Sign in</a>
        <a class="cart-link" href="{{ route('register') }}">Register</a>
    @endauth
</div>
