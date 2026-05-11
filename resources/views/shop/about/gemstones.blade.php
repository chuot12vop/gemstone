@extends('layouts.shop')

@section('content')
<header class="page-head">
    <h1 class="page-head__title">About Our {{ config('app.name') }}</h1>
    <p class="page-head__summary">More than jewelry — a bridge between mindful tradition and contemporary life.</p>
</header>
<div class="prose">
    <p>We believe meaningful spiritual tools shouldn't be rushed; they must be nurtured. 
        We take the time to understand each stone before it is chosen. 
        Every design is crafted with reverence to achieve energetic balance, 
        effortless wearability, and a pure, authentic beauty suited for American customers 
        who seek deep, genuine connections.</p>
    <p>Our palette is an ode to daylight: warm cream, champagne gold, 
        and soft neutrals—as elegant and quiet as nature itself.</p>
</div>
@endsection
