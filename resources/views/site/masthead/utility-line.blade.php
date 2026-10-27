{{-- The slim utility line above the bar: wide screens only, hidden while the bar is compact. Champagne, not brass: over ivory pages the glass blend leaves brass at 12px under 4.5:1. --}}
<div class="masthead-utility hidden xl:block" aria-label="{{ __('Site notice') }}">
    <div class="masthead-utility__inner mx-auto flex max-w-7xl items-center justify-between gap-6 px-4 sm:px-6">
        <p class="t-label text-ink">{{ $utility['line'] }}</p>
        <p class="flex items-center gap-6">
            @if ($account !== null)
                <span class="t-label text-ink" data-signed-in-as>{{ __('Signed in as :name', ['name' => $account['name']]) }}</span>
            @endif
            <a href="{{ $utility['link']['url'] }}" class="masthead-utility__link t-label">{{ $utility['link']['label'] }}</a>
        </p>
    </div>
</div>
