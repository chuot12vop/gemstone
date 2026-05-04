@extends('layouts.shop')

@section('content')
<header class="page-head">
    <h1 class="page-head__title">{{ $currentCategory ? $currentCategory->name : 'Catalog' }}</h1>
    @if($currentCategory && $currentCategory->description)
        <p class="page-head__summary">{{ $currentCategory->description }}</p>
    @else
        <p class="page-head__summary">Browse healing gemstones, lucky motifs, and limited designs.</p>
    @endif
</header>

<nav class="subnav" aria-label="Categories">
    <a class="subnav__link {{ $currentCategory === null ? 'is-active' : '' }}" href="{{ route('shop.catalog') }}">All</a>
    @foreach($categories as $c)
        <a class="subnav__link {{ $currentCategory && $currentCategory->id === $c->id ? 'is-active' : '' }}"
           href="{{ route('shop.catalog.category', $c) }}">{{ $c->name }}</a>
    @endforeach
</nav>

<div class="product-grid">
    @foreach($products as $p)
        <article class="product-card">
            <a href="{{ route('shop.product', $p) }}" class="product-card__link">
                <div class="product-card__img-wrap">
                    <img src="{{ $p->image ?: asset('assets/img/placeholder.svg') }}" alt="" width="400" height="400" loading="lazy">
                </div>
                <h2 class="product-card__title">{{ $p->name }}</h2>
                <p class="product-card__price">{{ $currency->formatUsd((float) $p->price_usd) }}</p>
            </a>
        </article>
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
