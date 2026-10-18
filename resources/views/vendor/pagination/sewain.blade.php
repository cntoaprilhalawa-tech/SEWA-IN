@if ($paginator->hasPages())
<nav class="pager" role="navigation" aria-label="Halaman">
    @if ($paginator->onFirstPage())
        <span class="off">&laquo;</span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" rel="prev">&laquo;</a>
    @endif

    @foreach ($elements as $element)
        @if (is_string($element))
            <span class="off">{{ $element }}</span>
        @endif
        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span class="on">{{ $page }}</span>
                @else
                    <a href="{{ $url }}">{{ $page }}</a>
                @endif
            @endforeach
        @endif
    @endforeach

    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" rel="next">&raquo;</a>
    @else
        <span class="off">&raquo;</span>
    @endif
</nav>
@endif
