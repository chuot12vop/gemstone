@extends('layouts.shop')

@section('mainClass', 'site-main--catalog')

@section('content')
@php
    $pageTitle = ! empty($filters['q'])
        ? 'Search: '.$filters['q']
        : ($currentCategory ? $currentCategory->name : ($currentBrand ? $currentBrand->name : 'Products'));

    $pageSummary = $currentCategory && $currentCategory->description
        ? $currentCategory->description
        : ($currentBrand
            ? 'Pieces from '.$currentBrand->name.'. Browse healing gemstones, lucky motifs, and limited designs.'
            : 'Browse healing gemstones, lucky motifs, and limited designs.');

    $categoryHeroSource = $currentCategory?->catalog_banner ?: $currentCategory?->image;
    $categoryHeroImage = $categoryHeroSource
        ? \App\Support\PublicAssetUrl::to($categoryHeroSource)
        : null;

    $activeFilterCount = 0;
    if (!empty($filters['brand_slug'])) {
        $activeFilterCount++;
    }
    if (!empty($filters['category_id'])) {
        $activeFilterCount++;
    }
    if (($filters['sort'] ?? 'related') !== 'related') {
        $activeFilterCount++;
    }
    if (! empty($filters['q'])) {
        $activeFilterCount++;
    }

    $isAllProducts = empty($filters['category_id']) && empty($filters['brand_slug']) && empty($filters['q']);
@endphp

<div class="catalog-missoma">
    <nav class="catalog-breadcrumb" aria-label="Breadcrumb">
        <ol class="catalog-breadcrumb__list">
            <li class="catalog-breadcrumb__item">
                <a href="{{ route('shop.home') }}">Home</a>
            </li>
            <li class="catalog-breadcrumb__item" aria-current="page">{{ $pageTitle }}</li>
        </ol>
    </nav>

    <header class="catalog-page-head">
        <h1 class="catalog-page-head__title">{{ $pageTitle }}</h1>
        @if($pageSummary && ! $categoryHeroImage)
            <div class="catalog-page-head__desc">
                <p class="catalog-page-head__summary is-collapsed" data-catalog-desc>{{ $pageSummary }}</p>
                <button type="button" class="catalog-page-head__toggle" data-catalog-desc-toggle hidden>Show more</button>
            </div>
        @endif
    </header>

    @if($categories->isNotEmpty())
        <nav class="catalog-chips" aria-label="Browse categories">
            <div class="catalog-chips__track">
                <a class="catalog-chips__link {{ $isAllProducts ? 'is-active' : '' }}"
                   href="{{ route('shop.products.index') }}">All products</a>
                @foreach($categories as $c)
                    @php
                        $chipActive = (int) ($filters['category_id'] ?? 0) === $c->id;
                    @endphp
                    <a class="catalog-chips__link {{ $chipActive ? 'is-active' : '' }}"
                       href="{{ route('shop.catalog.category', $c) }}"
                       @if($chipActive) aria-current="page" @endif>{{ $c->name }}</a>
                @endforeach
            </div>
        </nav>
    @endif

    <div class="catalog-toolbar">
        <button type="button"
                class="catalog-toolbar__filters-btn"
                data-catalog-open-filters
                aria-expanded="false"
                aria-controls="catalog-filters-panel">
            <svg class="catalog-toolbar__filters-icon" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <path d="M4 6h16M7 12h10M10 18h4" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
            </svg>
            Filter
            @if($activeFilterCount > 0)
                <span class="catalog-toolbar__badge">{{ $activeFilterCount }}</span>
            @endif
        </button>

        <form class="catalog-toolbar__sort" method="get" action="{{ route('shop.products.index') }}">
            @if(!empty($filters['category_id']))
                <input type="hidden" name="category_id" value="{{ $filters['category_id'] }}">
            @endif
            @if(!empty($filters['brand_slug']))
                <input type="hidden" name="brand" value="{{ $filters['brand_slug'] }}">
            @endif
            @if(!empty($filters['q']))
                <input type="hidden" name="q" value="{{ $filters['q'] }}">
            @endif
            <label class="catalog-toolbar__sort-label">
                <select
                    name="sort"
                    class="catalog-toolbar__sort-select"
                    aria-label="Sort products"
                    onchange="this.form.submit()"
                >
                    <option value="newest" @selected(($filters['sort'] ?? 'related') === 'newest')>Newest</option>
                    <option value="related" @selected(($filters['sort'] ?? 'related') === 'related')>Featured</option>
                    <option value="price_desc" @selected(($filters['sort'] ?? 'related') === 'price_desc')>Price: high to low</option>
                    <option value="price_asc" @selected(($filters['sort'] ?? 'related') === 'price_asc')>Price: low to high</option>
                </select>
            </label>
        </form>
    </div>

    <div id="catalog-filters-panel"
         class="catalog-filters-drawer"
         data-catalog-filters-panel
         hidden>
        <button type="button" class="catalog-filters-drawer__backdrop" data-catalog-close-filters tabindex="-1" aria-hidden="true"></button>
        <aside class="catalog-filters-drawer__panel" role="dialog" aria-modal="true" aria-labelledby="catalog-filters-title">
            <header class="catalog-filters-drawer__head">
                <h2 id="catalog-filters-title" class="catalog-filters-drawer__title">Filter</h2>
                <button type="button" class="catalog-filters-drawer__close" data-catalog-close-filters aria-label="Close filters">&times;</button>
            </header>
            <form class="catalog-filters" method="get" action="{{ route('shop.products.index') }}" data-catalog-filter-form>
                @if(!empty($filters['q']))
                    <input type="hidden" name="q" value="{{ $filters['q'] }}">
                @endif
                <label>
                    Category
                    <select name="category_id" data-catalog-category-filter>
                        <option value="">All categories</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}" @selected((int) ($filters['category_id'] ?? 0) === $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Brand
                    <select name="brand" data-catalog-brand-filter>
                        <option value="">All brands</option>
                        @foreach($brands as $b)
                            @php
                                $categoryIds = $brandCategoryIds->get($b->id, []);
                                $brandMatchesCategory = empty($filters['category_id'])
                                    || in_array((int) $filters['category_id'], $categoryIds, true);
                            @endphp
                            <option
                                value="{{ $b->slug }}"
                                data-brand-categories="{{ implode(',', $categoryIds) }}"
                                @selected(($filters['brand_slug'] ?? '') === $b->slug)
                                @disabled(! $brandMatchesCategory)
                            >{{ $b->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Sort by
                    <select name="sort" data-catalog-sort-filter>
                        <option value="newest" @selected(($filters['sort'] ?? 'related') === 'newest')>Newest</option>
                        <option value="related" @selected(($filters['sort'] ?? 'related') === 'related')>Featured</option>
                        <option value="price_desc" @selected(($filters['sort'] ?? 'related') === 'price_desc')>Price: high to low</option>
                        <option value="price_asc" @selected(($filters['sort'] ?? 'related') === 'price_asc')>Price: low to high</option>
                    </select>
                </label>
                <div class="catalog-filters__actions">
                    <button class="btn btn--primary btn--small" type="submit">Apply</button>
                    <a class="btn btn--ghost btn--small" href="{{ route('shop.products.index') }}">Reset</a>
                </div>
            </form>
        </aside>
    </div>

    @if($categoryHeroImage)
        <div class="catalog-category-hero" aria-label="{{ $currentCategory->name }}">
            <div class="catalog-category-hero__media">
                <img class="catalog-category-hero__img"
                     src="{{ $categoryHeroImage }}"
                     alt="{{ $currentCategory->name }}"
                     width="1400"
                     height="280"
                     loading="lazy">
                @if($pageSummary)
                    <div class="catalog-category-hero__content">
                        <h2 class="catalog-category-hero__title">{{ $currentCategory->name }}</h2>
                        <p class="catalog-category-hero__description">{{ $pageSummary }}</p>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <div class="shop-product-grid shop-product-grid--catalog" data-catalog-grid>
        @foreach($products as $p)
            @include('shop.partials.product-card', ['product' => $p, 'currency' => $currency])
        @endforeach
    </div>

    @if($products->hasMorePages())
        <div class="catalog-show-more-wrap">
            <button type="button"
                    class="btn btn--ghost catalog-show-more"
                    data-catalog-show-more
                    data-next-url="{{ $products->nextPageUrl() }}">
                Show more
            </button>
        </div>
    @endif
</div>
@endsection
