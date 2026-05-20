@extends('layouts.shop')

@section('content')
<header class="page-head">
    <h1 class="page-head__title">About Our {{ $siteName }}</h1>
    @if(!empty($about['page_summary']))
        <p class="page-head__summary">{{ $about['page_summary'] }}</p>
    @endif
</header>
@if(!empty($about['page_body']))
    <div class="prose post-detail__body">{!! $about['page_body'] !!}</div>
@endif
@endsection
