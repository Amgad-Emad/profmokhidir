{{-- A file: opens in a new tab and says what it is ("Download · PDF · 2.1 MB"). --}}
<a href="{{ $href }}" target="_blank" rel="noopener" {{ $attributes->class(['link-meta']) }}>{{ $text() }}<span class="sr-only"> {{ __('(opens in a new tab)') }}</span></a>
