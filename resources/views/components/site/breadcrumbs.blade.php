{{-- Breadcrumb trail in small caps: Home / Section / Page. The last crumb is the current page. --}}
<nav {{ $attributes->class(['breadcrumbs', 'breadcrumbs--ivory' => $tone === 'ivory']) }} aria-label="{{ $ariaLabel() }}">
    <ol class="breadcrumbs__list">
        @foreach ($trail as $crumb)
            <li class="breadcrumbs__item">
                @if ($crumb['current'])
                    <a href="{{ $crumb['url'] }}" class="breadcrumbs__link smallcaps" aria-current="page">{{ $crumb['label'] }}</a>
                @else
                    <a href="{{ $crumb['url'] }}" class="breadcrumbs__link link-draw smallcaps">{{ $crumb['label'] }}</a>
                    <span class="breadcrumbs__separator" aria-hidden="true">{{ $separator() }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
