{{--
    One "Needs your attention" card on the dashboard: what is waiting (title and count), why it
    matters in a sentence, up to a few records with links, and the one-click action.
--}}
@props(['card'])
<article {{ $attributes->class(['attention-card']) }} aria-labelledby="attention-{{ $card['key'] }}" data-queue="{{ $card['key'] }}">
    <p class="attention-card__count" aria-hidden="true">{{ $card['count'] }}</p>
    <h3 id="attention-{{ $card['key'] }}" class="attention-card__title"><span class="sr-only">{{ $card['count'] }} · </span>{{ $card['title'] }}</h3>
    <p class="attention-card__detail">{{ $card['detail'] }}</p>
    @if ($card['items'] !== [])
        <ul class="attention-card__items">
            @foreach ($card['items'] as $item)
                <li><a href="{{ $item['url'] }}">{{ $item['label'] }}</a><span class="block text-xs font-light text-ink-muted">{{ $item['meta'] }}</span></li>
            @endforeach
        </ul>
    @endif
    <a href="{{ $card['url'] }}" class="attention-card__action" data-attention-action>{{ $card['action'] }}<x-icon name="arrow" class="size-4" /></a>
</article>
