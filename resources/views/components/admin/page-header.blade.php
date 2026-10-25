{{-- Admin page heading: group eyebrow, display title, optional intro and actions on the right. --}}
@props(['eyebrow', 'title', 'intro' => null])
<header {{ $attributes->class(['flex flex-col gap-6 md:flex-row md:items-end md:justify-between']) }}>
    <div class="flex flex-col gap-2">
        <p class="t-label text-accent-text">{{ $eyebrow }}</p>
        <h1 class="t-heading">{{ $title }}</h1>
        @if ($intro)
            <p class="max-w-2xl text-base font-normal text-ink-muted">{{ $intro }}</p>
        @endif
    </div>
    @if (trim($slot) !== '')
        <div class="flex flex-wrap items-center gap-3">{{ $slot }}</div>
    @endif
</header>
