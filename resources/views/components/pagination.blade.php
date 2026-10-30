@if ($paginator->hasPages())
    @php
        $pages = method_exists($paginator, 'elements') ? $paginator->elements() : [$paginator->currentPage() => $paginator->url($paginator->currentPage())];
    @endphp

    <nav class="ef-pagination" role="navigation" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="ef-pagination__item is-disabled" aria-disabled="true">Previous</span>
        @else
            <a class="ef-pagination__item" href="{{ $paginator->previousPageUrl() }}" rel="prev">Previous</a>
        @endif

        <ol class="ef-pagination__pages">
            @foreach ($pages as $element)
                @if (is_string($element))
                    <li class="ef-pagination__gap" aria-hidden="true">{{ $element }}</li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li><span class="ef-pagination__item is-current" aria-current="page">{{ $page }}</span></li>
                        @else
                            <li><a class="ef-pagination__item" href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </ol>

        @if ($paginator->hasMorePages())
            <a class="ef-pagination__item" href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
        @else
            <span class="ef-pagination__item is-disabled" aria-disabled="true">Next</span>
        @endif
    </nav>
@endif