{{--
    <x-video-player :player="$player" :title="$video->title" />
    `player` comes from VideosPageService::player(). Hosted videos sit behind a facade: the poster is
    our own image and the iframe (youtube-nocookie.com / Vimeo with dnt=1) is created only when the
    visitor presses play. Without JavaScript the play control is a plain link to the hosting page.
--}}
@props(['player', 'title', 'featured' => false])
@php
    $aspect = \App\Constants\VideoEmbed::ASPECT;
    $poster = $player['poster'];
    $posterUrl = $poster ? \App\Support\ResponsiveImage::largestUrl($poster) : null;
@endphp
<div {{ $attributes->class(['video-player', 'video-player--featured' => $featured]) }}>
    @if ($player['kind'] === \App\Constants\VideoEmbed::KIND_EMBED)
        <div class="video-facade on-dark relative overflow-hidden rounded-card bg-surface" style="aspect-ratio: {{ $aspect }}" x-data="videoFacade">
            <div class="absolute inset-0" x-show="! playing">
                @if ($poster)
                    <x-photo :media="$poster" :aspect="$aspect" :captioned="false" :sizes="$featured ? '(min-width: 1280px) 1232px, 100vw' : '(min-width: 1024px) 896px, 100vw'" :loading="$featured ? 'eager' : 'lazy'" class="h-full" />
                @else
                    <div class="glow-brass h-full w-full"></div>
                @endif
                <a href="{{ $player['url'] }}" rel="noopener" class="video-facade__play" @click.prevent="play()">
                    <span class="video-facade__ring" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M8 5.5v13l11-6.5z" /></svg>
                    </span>
                    <span class="t-label">Play</span>
                    <span class="sr-only">{{ $title }}</span>
                </a>
            </div>
            <template x-if="playing">
                <iframe
                    x-ref="frame"
                    class="absolute inset-0 h-full w-full border-0"
                    src="{{ $player['embed_url'] }}"
                    title="{{ $title }}"
                    allow="{{ \App\Constants\VideoEmbed::IFRAME_ALLOW }}"
                    allowfullscreen
                    referrerpolicy="strict-origin-when-cross-origin"
                ></iframe>
            </template>
        </div>
        <p class="mt-4"><x-external-link :href="$player['url']" :label="$player['label']" /></p>
    @elseif ($player['kind'] === \App\Constants\VideoEmbed::KIND_FILE)
        <video
            controls
            preload="none"
            playsinline
            class="w-full rounded-card bg-surface"
            style="aspect-ratio: {{ $aspect }}"
            @if ($posterUrl) poster="{{ $posterUrl }}" @endif
        >
            <source src="{{ $player['file']->getUrl() }}"{!! $player['mime'] ? ' type="'.e($player['mime']).'"' : '' !!}>
            @if ($player['captions_url'])
                <track kind="captions" src="{{ $player['captions_url'] }}" srclang="{{ app()->getLocale() }}" label="Captions" default>
            @endif
            <p class="t-body p-6 text-ink">This browser cannot play the video. <a href="{{ $player['file']->getUrl() }}" class="text-ink-strong">Download the file</a> to watch it.</p>
        </video>
    @else
        <div class="card flex flex-col items-start gap-6">
            @if ($poster)
                <x-photo :media="$poster" :aspect="$aspect" :captioned="false" sizes="(min-width: 1024px) 896px, 100vw" class="w-full" />
            @endif
            <p class="t-body text-ink-muted">This recording is hosted on another site.</p>
            @if ($player['url'])
                <x-button variant="filled" :href="$player['url']" rel="noopener">{{ $player['label'] }}</x-button>
            @endif
        </div>
    @endif
</div>
