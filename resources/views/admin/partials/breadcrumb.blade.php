@php
    /** @var array<int, array{label: string, url?: string}> $breadcrumbs */
    $breadcrumbs = $breadcrumbs ?? [];
@endphp

@if(!empty($breadcrumbs))
    <nav class="admin-breadcrumb" aria-label="Breadcrumb">
        <ol>
            <li>
                <a href="{{ route('admin.dashboard') }}">
                    <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true"><path fill="currentColor" d="M12 3 2 12h3v8h6v-6h2v6h6v-8h3z"/></svg>
                    <span class="sr-only">Home</span>
                </a>
            </li>
            @foreach($breadcrumbs as $i => $crumb)
                <li>
                    <span class="admin-breadcrumb__sep" aria-hidden="true">/</span>
                    @if(!empty($crumb['url']) && $i < count($breadcrumbs) - 1)
                        <a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>
                    @else
                        <span aria-current="page">{{ $crumb['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
