{{--
    <x-section-card :card="$card" /> inside <ul class="section-cards">: one card from SectionCardService::cards().
    The whole card is one link named "Explore <section>" (the numeral, strip and arrow are hidden from
    assistive technology; the intro is the link's description). App\Constants\SectionCards holds the sizes.
--}}
@props(['card', 'level' => 3])
@use('App\Constants\MediaProperties')
@use('App\Constants\SectionCards')
@use('App\Support\ResponsiveImage')
@php
    $id = 'section-card-'.$card['key'];
    $image = $card['image'] ?? null;
    $focus = $image ? [$image->getCustomProperty(MediaProperties::FOCUS_X), $image->getCustomProperty(MediaProperties::FOCUS_Y)] : [null, null];
    $position = is_numeric($focus[0]) && is_numeric($focus[1]) ? sprintf('%d%% %d%%', max(0, min(100, (int) $focus[0])), max(0, min(100, (int) $focus[1]))) : null;
@endphp
<a href="{{ $card['url'] }}" {{ $attributes->class(['section-card']) }} aria-labelledby="{{ $id }}-action {{ $id }}-title" @if (filled($card['summary'])) aria-describedby="{{ $id }}-summary" @endif data-section-card="{{ $card['key'] }}">
    @if ($image)
        <span class="section-card__strip" aria-hidden="true">
            <img src="{{ ResponsiveImage::largestUrl($image) }}" @if ($srcset = ResponsiveImage::srcset($image)) srcset="{{ $srcset }}" sizes="{{ SectionCards::IMAGE_SIZES }}" @endif alt="" class="{{ ResponsiveImage::CLASS_ARCHIVE }}" @if ($position) style="object-position: {{ $position }}" @endif loading="lazy" decoding="async">
        </span>
    @endif
    <span class="section-card__numeral t-label" aria-hidden="true">{{ $card['numeral'] }}</span>
    <span class="section-card__title" id="{{ $id }}-title" role="heading" aria-level="{{ $level }}">{{ $card['title'] }}</span>
    @if (filled($card['summary']))
        <span class="section-card__summary" id="{{ $id }}-summary">{{ $card['summary'] }}</span>
    @endif
    <span class="section-card__action"><span id="{{ $id }}-action">{{ SectionCards::ACTION }}</span> <span class="section-card__arrow" aria-hidden="true">{{ SectionCards::ARROW }}</span></span>
</a>
