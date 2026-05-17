@php($titleTag = $titleTag ?? 'h3')
<article class="home-news-card">
    @if($post->image)
        <a class="home-news-card__media" href="{{ route('shop.post.show', $post) }}">
            <img src="{{ \App\Support\PublicAssetUrl::to($post->image) }}" alt="" loading="lazy" width="400" height="240">
        </a>
    @endif
    <div class="home-news-card__body">
        @if($post->published_at)
            <time class="home-news-card__date" datetime="{{ $post->published_at->toIso8601String() }}">{{ $post->published_at->format('M j, Y') }}</time>
        @endif
        <{{ $titleTag }} class="home-news-card__title"><a href="{{ route('shop.post.show', $post) }}">{{ $post->title }}</a></{{ $titleTag }}>
        @if($post->excerpt)
            <p class="home-news-card__excerpt">{{ $post->excerpt }}</p>
        @endif
        <a class="home-news-card__link" href="{{ route('shop.post.show', $post) }}">Read more →</a>
    </div>
</article>
