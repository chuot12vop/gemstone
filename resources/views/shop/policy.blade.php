@extends('layouts.shop')

@section('content')
<header class="page-head">
    <h1 class="page-head__title">{{ $heading }}</h1>
</header>
<div class="prose">
    @if($content !== strip_tags($content))
        {!! $content !!}
    @else
        {!! nl2br(e($content)) !!}
    @endif
</div>
@endsection
