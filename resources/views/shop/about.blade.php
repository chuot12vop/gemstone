@extends('layouts.shop')

@section('content')
@php($pageTitle = trim($about['page_title'] ?? '') ?: 'About Our '.$siteName)
<header class="page-head">
    <h1 class="page-head__title">{{ $pageTitle }}</h1>
    @if(!empty($about['page_summary']))
        <p class="page-head__summary">{{ $about['page_summary'] }}</p>
    @endif
</header>

<div class="about-page">
    @if(!empty($about['page_body']))
        <div class="prose post-detail__body about-page__body">{!! $about['page_body'] !!}</div>
    @endif

    @if(!empty($about['panels']))
        <div class="about-page__panels home-about">
            @include('shop.partials.about-panels', ['panels' => $about['panels']])
        </div>
    @endif
</div>
@endsection
