@if ($paginator->hasPages())
    <nav class="portal-pagination" role="navigation" aria-label="Paginação">
        @if ($paginator->onFirstPage())
            <span class="portal-pagination-link is-disabled" aria-disabled="true">Anterior</span>
        @else
            <a class="portal-pagination-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">Anterior</a>
        @endif

        <ul class="portal-pagination-pages">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="portal-pagination-dots">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span class="portal-pagination-link is-current" aria-current="page">{{ $page }}</span>
                            @else
                                <a class="portal-pagination-link" href="{{ $url }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach
        </ul>

        @if ($paginator->hasMorePages())
            <a class="portal-pagination-link" href="{{ $paginator->nextPageUrl() }}" rel="next">Próxima</a>
        @else
            <span class="portal-pagination-link is-disabled" aria-disabled="true">Próxima</span>
        @endif
    </nav>
@endif
