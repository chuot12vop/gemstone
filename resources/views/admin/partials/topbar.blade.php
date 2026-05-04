@php
    /** @var \App\Models\Admin|null $admin */
    $admin = Auth::guard('admin')->user();
    $initial = $admin ? strtoupper(mb_substr($admin->name ?: $admin->email, 0, 1)) : 'A';
@endphp

<header class="admin-topbar" role="banner">
    <button type="button"
            class="admin-topbar__menu"
            data-admin-mobile-toggle
            aria-controls="admin-sidebar"
            aria-label="Open navigation">
        <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path fill="currentColor" d="M3 6h18v2H3zm0 5h18v2H3zm0 5h18v2H3z"/></svg>
    </button>

    <form class="admin-topbar__search" method="get" action="{{ route('admin.products.index') }}" role="search">
        <label class="sr-only" for="admin-q">Search</label>
        <span class="admin-topbar__search-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M15.5 14h-.79l-.28-.27a6.5 6.5 0 1 0-.7.7l.27.28v.79l5 5 1.5-1.5-5-5zM10 14a4 4 0 1 1 4-4 4 4 0 0 1-4 4z"/></svg>
        </span>
        <input id="admin-q" type="search" name="q" placeholder="Search products, categories…" value="{{ request('q') }}">
    </form>

    <div class="admin-topbar__actions">
        <div class="admin-pop" data-admin-pop>
            <button type="button" class="admin-icon-btn" data-admin-pop-btn aria-haspopup="true" aria-expanded="false" aria-label="Notifications">
                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M12 22a2 2 0 0 0 2-2h-4a2 2 0 0 0 2 2zm6-6V11a6 6 0 1 0-12 0v5l-2 2v1h16v-1z"/></svg>
                @isset($notifyCount)
                    @if($notifyCount > 0)
                        <span class="admin-icon-btn__dot" aria-hidden="true">{{ $notifyCount > 9 ? '9+' : $notifyCount }}</span>
                    @endif
                @endisset
            </button>
            <div class="admin-menu" role="menu" data-admin-pop-panel>
                <p class="admin-menu__head">Notifications</p>
                @forelse($notifications ?? [] as $n)
                    <a href="{{ $n['url'] ?? '#' }}" class="admin-menu__item">
                        <strong>{{ $n['title'] ?? 'Update' }}</strong>
                        <span>{{ $n['body'] ?? '' }}</span>
                    </a>
                @empty
                    <p class="admin-menu__empty">No new notifications.</p>
                @endforelse
            </div>
        </div>

        <div class="admin-pop" data-admin-pop>
            <button type="button" class="admin-avatar" data-admin-pop-btn aria-haspopup="true" aria-expanded="false">
                <span class="admin-avatar__circle" aria-hidden="true">{{ $initial }}</span>
                <span class="admin-avatar__meta">
                    <span class="admin-avatar__name">{{ $admin?->name ?? 'Admin' }}</span>
                    <span class="admin-avatar__role">Administrator</span>
                </span>
            </button>
            <div class="admin-menu admin-menu--right" role="menu" data-admin-pop-panel>
                <p class="admin-menu__head">{{ $admin?->email ?? '' }}</p>
                <a class="admin-menu__item" href="{{ route('admin.dashboard') }}">Dashboard</a>
                <a class="admin-menu__item" href="{{ route('shop.home') }}" target="_blank" rel="noopener">View storefront</a>
                <form method="post" action="{{ route('admin.logout') }}" class="admin-menu__form">
                    @csrf
                    <button type="submit" class="admin-menu__item admin-menu__item--danger">Sign out</button>
                </form>
            </div>
        </div>
    </div>
</header>
