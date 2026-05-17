@extends('layouts.shop')

@section('content')
<header class="page-head">
    <h1 class="page-head__title">Journal</h1>
    <p class="page-head__summary">News, stories, and guides from our studio.</p>
</header>

@if($posts->isEmpty())
    <p class="home-section__empty home-section__empty--center">No articles yet — check back soon.</p>
@else
    <div class="home-news-grid">
        @foreach($posts as $post)
            @include('shop.partials.post-card', ['post' => $post, 'titleTag' => 'h2'])
        @endforeach
    </div>

    @if($posts->hasPages())
        <nav class="pagination-wrap" aria-label="Articles pagination">
            {{ $posts->links() }}
        </nav>
    @endif
@endif
@endsection
