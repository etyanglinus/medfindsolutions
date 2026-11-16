<div class="row">
    <div class="col-12">
        <div class="pagination">
            <ul class="page-numbers">
                @if ($paginator->onFirstPage())

                @else
                    <li><a aria-label="{{ __('Previous') }}" href="{{ $paginator->previousPageUrl() }}"><i class="fas fa-long-arrow-alt-left"></i></a>
                    </li>
                @endif

                @foreach ($elements as $element)

                    @if (is_array($element))
                        @foreach ($element as $key => $el)
                            <li>
                                @if ($key == $paginator->currentPage())
                                    <span>{{ $key }}</span>
                                @else
                                    <a aria-label="{{ $key }}" href="{{ $el }}">{{ $key }}</a>
                                @endif
                            </li>
                        @endforeach
                    @else
                        <li><span>{{ $element }}</span></li>
                    @endif
                @endforeach


                @if ($paginator->hasMorePages())
                 <li><a aria-label="{{ __('Next') }}" href="{{ $paginator->nextPageUrl() }}"><i class="fas fa-long-arrow-alt-right"></i></a>
                    </li>
                @endif

            </ul>
        </div>
    </div>
</div>
