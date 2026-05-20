<nav class="admin-pagination" aria-label="Pagination">
    @if($paginator->onFirstPage())
        <span class="admin-pagination__btn is-disabled">Previous</span>
    @else
        <a class="admin-pagination__btn" href="{{ $paginator->previousPageUrl() }}">Previous</a>
    @endif

    <span class="admin-pagination__info">
        Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}
    </span>

    @if($paginator->hasMorePages())
        <a class="admin-pagination__btn" href="{{ $paginator->nextPageUrl() }}">Next</a>
    @else
        <span class="admin-pagination__btn is-disabled">Next</span>
    @endif
</nav>
