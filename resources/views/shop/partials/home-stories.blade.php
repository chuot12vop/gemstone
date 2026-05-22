@php
    $storyImage = $storyPage->image ? \App\Support\PublicAssetUrl::to($storyPage->image) : null;
@endphp
<section class="home-section home-section--stories reveal-on-scroll" aria-labelledby="home-stories-title">
    <div class="home-stories">
        <div class="home-stories__layout">
            @if($storyImage)
                <div class="home-stories__media home-stories__reveal home-stories__reveal--from-start">
                    <img class="home-stories__img"
                         src="{{ $storyImage }}"
                         alt="{{ $storyPage->title }}"
                         width="480"
                         height="480"
                         loading="lazy">
                </div>
            @endif
            <div class="home-stories__body home-stories__reveal home-stories__reveal--from-end">
                <h3 class="home-stories__heading">{{ $storyPage->title }}</h3>
                @if(!empty($storyPage->description))
                    <div class="home-stories__description">{!! nl2br(e($storyPage->description)) !!}</div>
                @endif
            </div>
        </div>
    </div>
</section>
