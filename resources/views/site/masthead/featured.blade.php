{{-- The featured item on the right of a panel: label, a small treated photograph when the item has one, title, quote and metadata. --}}
<div class="mega-menu__featured">
    <p class="t-label text-accent-text">{{ $featured['label'] }}</p>
    <a href="{{ $featured['url'] }}" class="featured-card" data-masthead-featured="{{ $featured['kind'] }}">
        @if ($featured['media'] !== null)
            <x-photo :media="$featured['media']" tone="ivory" sizes="(min-width: 1024px) 14rem, 100vw" :captioned="false" :label="false" aspect="4 / 3" class="featured-card__photo" />
        @endif
        <span class="featured-card__title">{{ $featured['title'] }}</span>
        @if ($featured['quote'] !== null)
            <span class="featured-card__quote">&ldquo;{{ $featured['quote'] }}&rdquo;</span>
        @endif
        @if ($featured['meta'] !== null)
            <span class="featured-card__meta smallcaps">{{ $featured['meta'] }}</span>
        @endif
    </a>
</div>
