<article class="shop-product-card">
    <a href="{{ route('shop.product', $product) }}" class="shop-product-card__media">
        <img src="{{ $product->image ?: asset('assets/img/placeholder.svg') }}" alt="{{ $product->name }}" width="400" height="400" loading="lazy">
    </a>
    <div class="shop-product-card__body">
        <p class="shop-product-card__category">{{ $product->category->name ?? 'Uncategorized' }}</p>
        <h3 class="shop-product-card__title">
            <a href="{{ route('shop.product', $product) }}">{{ $product->name }}</a>
        </h3>
        <p class="shop-product-card__desc">{{ $product->short_description ?: 'Handcrafted gemstone product for daily wear and mindful living.' }}</p>
        <p class="shop-product-card__price">{{ $currency->formatUsd((float) $product->price_usd) }}</p>
        <form class="shop-product-card__form" method="post" action="{{ route('shop.cart.add') }}">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <input type="hidden" name="quantity" value="1">
            <button class="btn btn--primary btn--small shop-product-card__btn" type="submit" {{ $product->stock < 1 ? 'disabled' : '' }}>
                {{ $product->stock < 1 ? 'Out of stock' : 'Add to cart' }}
            </button>
        </form>
    </div>
</article>
