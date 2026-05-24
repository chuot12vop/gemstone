@extends('layouts.shop')

@section('content')
<header class="page-head">
    <h1 class="page-head__title">{{ $currentCategory ? $currentCategory->name : ($currentBrand ? $currentBrand->name : 'Products') }}</h1>
    @if($currentCategory && $currentCategory->description)
        <p class="page-head__summary">{{ $currentCategory->description }}</p>
    @elseif($currentBrand)
        <p class="page-head__summary">Pieces from {{ $currentBrand->name }}. Browse healing gemstones, lucky motifs, and limited designs.</p>
    @else
        <p class="page-head__summary">Browse healing gemstones, lucky motifs, and limited designs.</p>
    @endif
</header>

@php
    $activeFilterCount = 0;
    if (!empty($filters['brand_slug'])) {
        $activeFilterCount++;
    }
    if (!empty($filters['category_id'])) {
        $activeFilterCount++;
    }
    if (($filters['min_price'] ?? '') !== '' && ($filters['min_price'] ?? '') !== null) {
        $activeFilterCount++;
    }
    if (($filters['max_price'] ?? '') !== '' && ($filters['max_price'] ?? '') !== null) {
        $activeFilterCount++;
    }
@endphp
<details class="catalog-filters-wrap" data-catalog-filters @if($activeFilterCount > 0) data-filters-active open @endif>
    <summary class="catalog-filters-wrap__summary">
        Filters
        @if($activeFilterCount > 0)
            <span class="catalog-filters-wrap__badge">{{ $activeFilterCount }}</span>
        @endif
    </summary>
    <form class="catalog-filters" method="get" action="{{ route('shop.products.index') }}" data-catalog-filter-form>
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
            Min price (USD)
            <input type="number" step="0.01" min="0" name="min_price" value="{{ $filters['min_price'] }}">
        </label>
        <label>
            Max price (USD)
            <input type="number" step="0.01" min="0" name="max_price" value="{{ $filters['max_price'] }}">
        </label>
        <div class="catalog-filters__actions">
            <button class="btn btn--primary btn--small" type="submit">Apply</button>
            <a class="btn btn--ghost btn--small" href="{{ route('shop.products.index') }}">Reset</a>
        </div>
    </form>
</details>

<div class="shop-product-grid">
    @foreach($products as $p)
        @include('shop.partials.product-card', ['product' => $p, 'currency' => $currency])
    @endforeach
</div>

@if($products->hasPages())
    <nav class="pagination" aria-label="Pagination">
        @for($i = 1; $i <= $products->lastPage(); $i++)
            <a class="pagination__link {{ $i === $products->currentPage() ? 'is-active' : '' }}"
               href="{{ $products->url($i) }}">{{ $i }}</a>
        @endfor
    </nav>
@endif
@endsection
