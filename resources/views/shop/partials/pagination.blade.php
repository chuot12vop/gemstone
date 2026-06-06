@if ($paginator->hasPages())
    @if ($paginator->total() > 0)
        <p class="pagination-wrap__summary">
            Showing
            <strong>{{ $paginator->firstItem() }}</strong>
            to
            <strong>{{ $paginator->lastItem() }}</strong>
            of
            <strong>{{ $paginator->total() }}</strong>
            results
        </p>
    @endif

    <ul class="pagination">
        @if ($paginator->onFirstPage())
            <li class="disabled" aria-disabled="true">
                <span aria-hidden="true">Previous</span>
            </li>
        @else
            <li>
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page">Previous</a>
            </li>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <li class="disabled" aria-disabled="true"><span>{{ $element }}</span></li>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li class="active" aria-current="page"><span>{{ $page }}</span></li>
                    @else
                        <li><a href="{{ $url }}" aria-label="Go to page {{ $page }}">{{ $page }}</a></li>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <li>
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page">Next</a>
            </li>
        @else
            <li class="disabled" aria-disabled="true">
                <span aria-hidden="true">Next</span>
            </li>
        @endif
    </ul>
@endif
