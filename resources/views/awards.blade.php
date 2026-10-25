{{--
    Awards as a roll of honour: an engraved-plaque feel made only of type and hairlines. Each award is
    centred, its name in Cormorant small caps, the awarding body and year below, separated by ornaments.
--}}
@inject('pageCopy', 'App\Services\PageContentService')
<x-layouts.public :title="$section['title']" :description="$section['summary']">
    <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28" aria-labelledby="section-title">
        <x-chapter-opening :number="$chapter" :label="$section['label']" :title="$section['title']" level="1" id="section-title">
            {{ $section['summary'] }}
        </x-chapter-opening>

        @if ($awards->isEmpty())
            <x-empty-state class="mt-6" :message="$pageCopy->text('awards', 'empty')" />
        @else
            <div class="honour-roll">
                <x-ornament type="double-rule" />
                <ol class="honour-roll__list">
                    @foreach ($awards as $award)
                        <li class="honour">
                            @unless ($loop->first)
                                <x-ornament type="rule-dot" class="honour__ornament" />
                            @endunless
                            <article class="honour__plaque" aria-labelledby="award-{{ $award->getKey() }}" data-rise>
                                <h2 id="award-{{ $award->getKey() }}" class="honour__name">@typeset($award->name)</h2>
                                @if (filled($award->organisation) || $award->year)
                                    <p class="honour__meta">
                                        @if (filled($award->organisation))<span class="honour__organisation">@typeset($award->organisation)</span>@endif
                                        @if (filled($award->organisation) && $award->year)<span class="honour__separator" aria-hidden="true">{{ \App\Constants\DateFormats::META_SEPARATOR }}</span>@endif
                                        @if ($award->year)<span class="honour__year">{{ $award->year }}</span>@endif
                                    </p>
                                @endif
                                @if (filled($award->description))
                                    <p class="honour__description">@typeset($award->description)</p>
                                @endif
                                @if (filled($award->source_url))
                                    <p class="honour__source"><x-external-link :href="$award->source_url" label="Source" /></p>
                                @endif
                            </article>
                        </li>
                    @endforeach
                </ol>
                <x-ornament type="double-rule" />
            </div>
        @endif

        <x-folio :section="$section['label']" position="bottom" class="mt-16" />
    </section>
</x-layouts.public>
