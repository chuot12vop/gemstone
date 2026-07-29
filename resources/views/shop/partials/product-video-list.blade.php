@if($product->videos->isNotEmpty())
    <section class="product-video-list" data-product-video-list aria-labelledby="product-video-list-title">
        <h2 id="product-video-list-title" class="product-video-list__title">See It IRL</h2>
        <div class="product-video-list__viewport" data-product-video-viewport>
            <div class="product-video-list__track">
                @foreach($product->videos as $videoIndex => $video)
                    <video class="product-video-list__video" data-product-list-video
                           src="{{ \App\Support\PublicAssetUrl::to($video->path) }}"
                           data-video-thumbnail muted playsinline preload="metadata"
                           tabindex="0" role="button"
                           aria-label="{{ $product->name }} video {{ $videoIndex + 1 }}"></video>
                @endforeach
            </div>
        </div>
        @if($product->videos->count() > 1)
            <button type="button" class="product-video-list__nav product-video-list__nav--prev" data-product-video-prev aria-label="Previous video">&#10094;</button>
            <button type="button" class="product-video-list__nav product-video-list__nav--next" data-product-video-next aria-label="Next video">&#10095;</button>
        @endif
    </section>

    <aside class="product-video-float" data-product-video-float hidden aria-hidden="true" aria-label="Product video player">
        <div class="product-video-float__frame">
            <button type="button" class="product-video-float__close" data-product-video-float-close aria-label="Close video">&times;</button>
            <video class="product-video-float__video" data-product-video-float-player controls playsinline preload="metadata"></video>
            <p class="product-video-float__caption">{{ $product->name }}</p>
        </div>
    </aside>
@endif
