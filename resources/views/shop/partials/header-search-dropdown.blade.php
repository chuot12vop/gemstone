<div class="header-search" data-header-search>
    <button type="button"
            class="header-search__toggle header-icon-link"
            data-header-search-toggle
            aria-expanded="false"
            aria-controls="header-search-panel"
            aria-label="Search">
        <svg class="header-icon-link__svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <circle cx="11" cy="11" r="6.25" fill="none" stroke="currentColor" stroke-width="1.8"/>
            <path d="M16.2 16.2L20.5 20.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
        </svg>
    </button>
    <div class="header-search__dropdown"
         id="header-search-panel"
         data-header-search-panel
         hidden
         inert>
        <form class="header-search__form" method="get" action="{{ route('shop.products.index') }}" role="search">
            <label class="sr-only" for="header-search-input">Search products</label>
            <input type="search"
                   id="header-search-input"
                   class="header-search__input"
                   name="q"
                   value="{{ request('q') }}"
                   placeholder="Search products…"
                   autocomplete="off"
                   data-header-search-input>
            <button type="submit" class="header-search__submit" aria-label="Search">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <circle cx="11" cy="11" r="6.25" fill="none" stroke="currentColor" stroke-width="1.8"/>
                    <path d="M16.2 16.2L20.5 20.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
            </button>
        </form>
    </div>
</div>
