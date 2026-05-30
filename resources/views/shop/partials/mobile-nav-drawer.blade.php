<div class="mobile-nav-drawer"
     data-nav-drawer
     hidden
     inert
     aria-hidden="true">
    <button type="button"
            class="mobile-nav-drawer__backdrop"
            data-nav-backdrop
            aria-label="Close menu"></button>
    <aside class="mobile-nav-drawer__panel" aria-label="Navigation menu">
        <header class="mobile-nav-drawer__head">
            <a class="logo mobile-nav-drawer__logo" href="{{ route('shop.home') }}">
                @if(!empty($siteSettings['site_logo']))
                    <img class="logo__img" src="{{ $siteSettings['site_logo'] }}" alt="">
                @endif
                <span class="logo__name">{{ $siteSettings['site_name'] ?? config('app.name') }}</span>
            </a>
            <button type="button" class="mobile-nav-drawer__close" data-nav-close aria-label="Close menu">
                <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false">
                    <path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
        </header>
        <nav class="site-nav site-nav--mobile"
             id="site-nav"
             data-nav-panel
             aria-label="Main menu">
            <div class="site-nav__body">
                @include('shop.partials.header-nav-menu', ['navPrefix' => 'mobile'])
            </div>
        </nav>
    </aside>
</div>
