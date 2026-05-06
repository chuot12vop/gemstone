@extends('layouts.shop', ['title' => 'Page not found', 'metaDescription' => ''])

@section('content')
<header class="page-head">
    <h1 class="page-head__title">Page not found</h1>
    <p class="page-head__summary">The page you requested does not exist.</p>
</header>
<p><a href="{{ route('shop.home') }}">Return home</a> · <a href="{{ route('shop.products.index') }}">Browse catalog</a></p>
@endsection
