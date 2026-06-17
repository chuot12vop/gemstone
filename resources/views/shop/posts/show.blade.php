@extends('layouts.shop')

@section('content')
<article class="post-detail">
    <header class="page-head">
        <p class="eyebrow">News</p>
        <h1 class="page-head__title">{{ $post->title }}</h1>
        @if($post->published_at)
            <p class="page-head__summary">{{ $post->published_at->format('F j, Y') }}</p>
        @endif
    </header>
    @if($postImage)
        <img class="post-detail__image" src="{{ $postImage }}" alt="" width="960" height="540" loading="eager">
    @endif
    @if($post->body)
        <div class="prose post-detail__body">{!! $post->body !!}</div>
    @elseif($post->excerpt)
        <p class="lede">{{ $post->excerpt }}</p>
    @endif
    <p><a class="btn btn--ghost" href="{{ route('shop.news.index') }}">&larr; Back to journal</a></p>
</article>
@endsection
