{{-- A miniature page in one mode of a theme: band, heading, text, a card and the solid button. Colours arrive as custom properties. --}}
@php
    $c = $colours[$mode];
    $vars = collect([
        'surface' => $c['surface'], 'raised' => $c['card-surface'], 'band' => $c['band-surface'], 'band-ink' => $c['ink-on-band'],
        'heading' => $c['ink-strong'], 'ink' => $c['ink-body'], 'muted' => $c['ink-muted'], 'accent' => $c['accent-line'],
        'label' => $c['accent-text'], 'fill' => $c['fill'], 'fill-ink' => $c['fill-ink'], 'rule' => $c['card-border'],
    ])->map(fn (string $value, string $name): string => "--mini-{$name}: {$value}")->implode('; ');
@endphp
<div class="theme-mini" style="{{ $vars }}" aria-hidden="true" data-mini="{{ $mode }}">
    <div class="theme-mini__band"><span class="theme-mini__mark"></span><span class="theme-mini__nav"></span></div>
    <div class="theme-mini__body">
        <span class="theme-mini__label"></span>
        <span class="theme-mini__heading"></span>
        <span class="theme-mini__line"></span>
        <span class="theme-mini__line theme-mini__line--short"></span>
        <span class="theme-mini__card"><span class="theme-mini__line"></span></span>
        <span class="theme-mini__button"></span>
    </div>
</div>
