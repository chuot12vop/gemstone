@php
    $navItems = [
        ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'icon' => 'M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z'],
        ['route' => 'admin.products.index', 'label' => 'Products', 'icon' => 'M21 8h-3V6a3 3 0 0 0-3-3H9a3 3 0 0 0-3 3v2H3v13h18V8zM8 6a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H8V6z'],
        ['route' => 'admin.categories.index', 'label' => 'Categories', 'icon' => 'M10 4H4a2 2 0 0 0-2 2v4h10V4zm10 0h-6v6h8V6a2 2 0 0 0-2-2zm-10 8H2v6a2 2 0 0 0 2 2h6v-8zm10 0h-8v8h6a2 2 0 0 0 2-2v-6z'],
        ['route' => 'admin.currency.index', 'label' => 'Currency', 'icon' => 'M12 1a11 11 0 1 0 11 11A11 11 0 0 0 12 1zm0 20a9 9 0 1 1 9-9 9 9 0 0 1-9 9zm.5-13.4V6h-1v1.6a3 3 0 0 0 .5 5.95 1.5 1.5 0 1 1-1.5 1.5H9a3 3 0 0 0 2.5 2.95V19h1v-1A3 3 0 0 0 12 12.05a1.5 1.5 0 1 1 1.5-1.5h1.5a3 3 0 0 0-2.5-2.95z'],
        ['route' => 'admin.orders.index', 'label' => 'Orders', 'icon' => 'M19 7h-3V6a4 4 0 0 0-8 0v1H5a1 1 0 0 0-1 1v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8a1 1 0 0 0-1-1zM10 6a2 2 0 0 1 4 0v1h-4V6zm9 14H5V9h2v2h2V9h6v2h2V9h2v11z'],
        ['route' => 'admin.payments.index', 'label' => 'Payments', 'icon' => 'M3 6a3 3 0 0 1 3-3h12a3 3 0 0 1 3 3v2H3V6zm0 4h18v8a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3v-8zm4 3v2h4v-2H7z'],
        ['route' => 'admin.reviews.index', 'label' => 'Reviews', 'icon' => 'M12 2l2.95 6.6 7.05.7-5.3 4.92 1.55 7.18L12 17.85 5.75 21.4 7.3 14.22 2 9.3l7.05-.7L12 2z'],
        ['route' => 'admin.contacts.index', 'label' => 'Contacts', 'icon' => 'M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z'],
        ['route' => 'admin.settings.index', 'label' => 'Settings', 'icon' => 'M19.14 12.94a7.43 7.43 0 0 0 0-1.88l2.03-1.58a.5.5 0 0 0 .12-.63l-1.92-3.32a.5.5 0 0 0-.6-.22l-2.39.96a7.7 7.7 0 0 0-1.62-.94l-.36-2.54A.5.5 0 0 0 13.9 2h-3.8a.5.5 0 0 0-.49.42l-.36 2.54c-.57.22-1.11.54-1.62.94l-2.39-.96a.5.5 0 0 0-.6.22L2.72 8.48a.5.5 0 0 0 .12.63l2.03 1.58a7.43 7.43 0 0 0 0 1.88l-2.03 1.58a.5.5 0 0 0-.12.63l1.92 3.32a.5.5 0 0 0 .6.22l2.39-.96c.5.4 1.04.72 1.62.94l.36 2.54a.5.5 0 0 0 .49.42h3.8a.5.5 0 0 0 .49-.42l.36-2.54c.57-.22 1.11-.54 1.62-.94l2.39.96a.5.5 0 0 0 .6-.22l1.92-3.32a.5.5 0 0 0-.12-.63l-2.03-1.58zM12 15.5A3.5 3.5 0 1 1 12 8a3.5 3.5 0 0 1 0 7.5z'],
    ];
    $current = Route::currentRouteName();
@endphp

<aside class="admin-sidebar" data-admin-sidebar aria-label="Primary navigation">
    <div class="admin-sidebar__top">
        <a class="admin-brand" href="{{ route('admin.dashboard') }}" aria-label="{{ config('app.name') }} admin">
            <span class="admin-brand__mark" aria-hidden="true">G</span>
            <span class="admin-brand__name">{{ config('app.name') }}</span>
        </a>
        <button type="button"
                class="admin-sidebar__collapse"
                data-admin-collapse
                aria-label="Toggle sidebar">
            <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
                <path fill="currentColor" d="M15.4 7.41 14 6l-6 6 6 6 1.4-1.41L10.83 12z"/>
            </svg>
        </button>
    </div>

    <nav class="admin-nav" aria-label="Modules">
        @foreach($navItems as $item)
            @php
                $isActive = $current && (
                    $current === $item['route']
                    || str_starts_with($current, str_replace('.index', '.', $item['route']))
                );
            @endphp
            <a href="{{ route($item['route']) }}"
               class="admin-nav__link {{ $isActive ? 'is-active' : '' }}"
               @if($isActive) aria-current="page" @endif>
                <span class="admin-nav__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="{{ $item['icon'] }}"/></svg>
                </span>
                <span class="admin-nav__label">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="admin-sidebar__foot">
        <a class="admin-nav__link admin-nav__link--ghost" href="{{ route('shop.home') }}" target="_blank" rel="noopener">
            <span class="admin-nav__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7zM19 19H5V5h7V3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7h-2v7z"/></svg>
            </span>
            <span class="admin-nav__label">View storefront</span>
        </a>
    </div>
</aside>
