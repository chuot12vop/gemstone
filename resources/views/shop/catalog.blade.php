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

@endphp



<header class="catalog-page-head page-head">

    <h1 class="catalog-page-head__title page-head__title">{{ $pageTitle }}</h1>

    @if($pageSummary)

        <p class="catalog-page-head__summary page-head__summary is-collapsed" data-catalog-desc>{{ $pageSummary }}</p>

        <button type="button" class="catalog-page-head__toggle" data-catalog-desc-toggle hidden>Show more</button>

    @endif

</header>



<div class="catalog-toolbar">

    <button type="button"

            class="catalog-toolbar__filters-btn"

            data-catalog-open-filters

            aria-expanded="false"

            aria-controls="catalog-filters-panel">

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

        <label>

            Sort by

            <select name="sort" onchange="this.form.submit()">

                <option value="newest" @selected(($filters['sort'] ?? 'related') === 'newest')>Newest</option>

                <option value="related" @selected(($filters['sort'] ?? 'related') === 'related')>Featured</option>

                <option value="price_desc" @selected(($filters['sort'] ?? 'related') === 'price_desc')>Price: high to low</option>

                <option value="price_asc" @selected(($filters['sort'] ?? 'related') === 'price_asc')>Price: low to high</option>

            </select>

        </label>

    </form>



    <p class="catalog-toolbar__count">{{ $products->total() }} {{ Str::plural('product', $products->total()) }}</p>

</div>



<div id="catalog-filters-panel" class="catalog-filters-panel" data-catalog-filters-panel hidden>

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

            <select name="brand">

                <option value="">All brands</option>

                @foreach($brands as $b)

                    <option value="{{ $b->slug }}" @selected(($filters['brand_slug'] ?? '') === $b->slug)>{{ $b->name }}</option>

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

</div>



<div class="shop-product-grid" data-catalog-grid>

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

@endsection

