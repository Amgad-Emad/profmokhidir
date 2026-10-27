{{-- The plain search page: a GET form and the grouped results, without any JavaScript. --}}
@use('App\Constants\Search')
<x-layouts.public :title="$title" :breadcrumb-title="$breadcrumbTitle" :description="__(Search::PROMPT)" :noindex="true">
    <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28" aria-labelledby="section-title">
        <div class="mx-auto max-w-3xl">
            <x-section-header :label="__(Search::LABEL)" :title="__(Search::TITLE)" level="1" id="section-title" />

            <form novalidate method="get" action="{{ route(Search::ROUTE) }}" role="search" class="search-form mt-12" data-rise>
                <div class="search-form__row form-style-underline mt-3">
                    <x-form.input type="search" :name="Search::PARAM" id="search-query" :label="__(Search::LABEL)" hide-label :optional="false" size="display" class="search-form__field"
                                  :value="$results['query']" :placeholder="__(Search::PLACEHOLDER)" autocomplete="off" spellcheck="false" :maxlength="Search::MAX_LENGTH" autofocus />
                    <x-button type="submit">{{ __(Search::BUTTON_LABEL) }}</x-button>
                </div>
                @if (! $canSeeMembersOnly)
                    <p class="mt-3 text-sm font-normal text-ink-muted">{{ __(Search::MEMBERS_NOTE) }}</p>
                @endif
            </form>

            <div class="search-results mt-16" role="region" aria-live="polite" aria-label="{{ __('Search results') }}">
                @include('search.results', ['results' => $results, 'searchable' => $searchable, 'sections' => $sections, 'tone' => 'evergreen', 'compact' => false])
            </div>
        </div>
    </section>
</x-layouts.public>
