@extends('layouts.shop')

@section('content')
<header class="page-head">
    <h1 class="page-head__title">{{ $heading }}</h1>
</header>
<div class="prose">
    {!! nl2br(e($content)) !!}
</div>
@endsection
