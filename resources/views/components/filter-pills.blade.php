{{--
    A row of filter links in the pill style. Each option is ['label' => ..., 'url' => ..., 'active' => bool].
    Links update the query string, so filtering works without JavaScript.
--}}
@props(['label', 'options' => [], 'allUrl' => null, 'allActive' => false, 'allLabel' => 'All'])
<nav {{ $attributes->merge(['aria-label' => $label]) }}>
    <p class="t-label text-bronze">{{ $label }}</p>
    <ul class="mt-4 flex flex-wrap gap-2">
        @if ($allUrl !== null)
            <li>
                <a href="{{ $allUrl }}" class="filter-pill"{!! $allActive ? ' aria-current="true"' : '' !!}>{{ $allLabel }}</a>
            </li>
        @endif
        @foreach ($options as $option)
            <li>
                <a href="{{ $option['url'] }}" class="filter-pill"{!! $option['active'] ? ' aria-current="true"' : '' !!}>{{ $option['label'] }}</a>
            </li>
        @endforeach
    </ul>
</nav>
