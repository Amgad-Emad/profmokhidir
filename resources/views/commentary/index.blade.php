@inject('terms', 'App\Services\TermTranslationService')
@inject('pageCopy', 'App\Services\PageContentService')
<x-layouts.public :title="$section['title']" :description="$section['summary']">
    <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28" aria-labelledby="section-title">
        <x-chapter-opening :number="$chapter" :label="$section['label']" :title="$section['title']" level="1" id="section-title">
            {{ $section['summary'] }}
        </x-chapter-opening>

        @if ($commentaries->total() === 0 && $activeTag === null)
            <x-empty-state class="mt-6" :message="$pageCopy->text('commentary', 'empty')" />
        @else
            @if ($tags !== [])
                <x-filter-pills class="mt-12 border-y border-rule py-8" :label="__('Topics')" :options="$tags" :all-url="$clearUrl" :all-active="$activeTag === null" :all-label="$pageCopy->text('commentary', 'all_topics')" data-rise />
            @endif

            @if ($commentaries->total() === 0)
                <x-empty-state class="mt-6" :message="$pageCopy->text('commentary', 'empty_topic')" />
            @else
                <ol class="mt-12 divide-y divide-rule border-b border-rule" data-rise-group>
                    @foreach ($commentaries as $commentary)
                        <li class="grid gap-3 py-10 sm:grid-cols-[10rem_minmax(0,1fr)] sm:gap-10">
                            <div class="pt-2">
                                @if ($commentary->date)
                                    <x-date :date="$commentary->date" class="t-label text-accent-text" />
                                @endif
                            </div>
                            <article class="flex flex-col gap-3">
                                <h2 class="font-display text-2xl font-light leading-snug text-ink sm:text-quote" @contentLang($commentary)>
                                    <a href="{{ route('commentary.show', $commentary->slug) }}" class="text-ink">{{ $commentary->title }}</a>
                                </h2>
                                @if (filled($commentary->summary))
                                    <p class="t-body max-w-measure text-ink" @contentLang($commentary, 'summary')>{{ $commentary->summary }}</p>
                                @endif
                                @if (filled($commentary->tags))
                                    <ul class="mt-2 flex flex-wrap gap-2" aria-label="{{ __('Topics') }}">
                                        @foreach ($commentary->tags as $tag)
                                            <li><a href="{{ route('commentary', ['tag' => $tag]) }}" class="filter-pill min-h-9 px-3 text-sm">{{ $terms->label(\App\Constants\TermVocabulary::CommentaryTag, $tag) }}</a></li>
                                        @endforeach
                                    </ul>
                                @endif
                            </article>
                        </li>
                    @endforeach
                </ol>

                {{ $commentaries->links('components.pagination') }}
            @endif
        @endif
    </section>
</x-layouts.public>
