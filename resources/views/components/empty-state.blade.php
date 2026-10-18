{{--
    Quiet notice for a list with nothing published. `tone` is the ink: "evergreen" on
    ivory surfaces (default) or "ivory" on evergreen surfaces.
--}}
@props(['message', 'tone' => 'evergreen'])
<div {{ $attributes->class(['empty-state flex flex-col items-start gap-5 py-12']) }}>
    <hr class="rule-brass-short" data-rule>
    <p class="font-display text-2xl font-light italic {{ $tone === 'ivory' ? 'text-champagne' : 'text-ash' }}">{{ $message }}</p>
</div>
