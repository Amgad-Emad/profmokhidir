{{--
    Search results grouped by section, shared by the /search page (ivory) and the header
    dialog (evergreen, `tone="ivory"`, JSON). `searchable` is false while the query is too
    short; an empty result shows an epigraph rather than a plain line.
--}}
@use('App\Constants\Search')
@php
    $onDark = ($tone ?? 'evergreen') === 'ivory';
    $compact = $compact ?? false;
@endphp
@if (! $searchable)
    <p class="search-results__prompt {{ $onDark ? 'text-champagne' : 'text-ash' }}">{{ Search::PROMPT }}</p>
@elseif ($results['groups'] === [])
    <x-empty-state :message="Search::NONE_FOUND" :attribution="Search::NONE_ATTRIBUTION" :tone="$onDark ? 'ivory' : 'evergreen'" class="search-results__empty" />
    <nav class="search-results__chapters" aria-label="Chapters">
        <ul class="search-results__chapter-list">
            @foreach ($sections as $item)
                <li><a href="{{ $item['url'] }}" class="search-results__chapter link-draw smallcaps">{{ $item['label'] }}</a></li>
            @endforeach
        </ul>
    </nav>
@else
    <p class="search-results__summary t-label {{ $onDark ? 'text-brass' : 'text-bronze' }}">{{ $results['summary'] }}</p>
    @foreach ($results['groups'] as $group)
        <section class="search-results__group" aria-labelledby="search-group-{{ $group['key'] }}">
            <h2 id="search-group-{{ $group['key'] }}" class="search-results__heading">
                <a href="{{ $group['url'] }}" class="link-draw">{{ $group['label'] }}</a>
                <span class="search-results__count smallcaps">{{ $group['total'] }}</span>
            </h2>
            <hr class="rule-brass search-results__rule">
            <ol class="search-results__list">
                @foreach ($group['items'] as $hit)
                    <li class="search-results__item">
                        <a href="{{ $hit['url'] }}" class="search-results__link" data-search-result>
                            <span class="search-results__title">{!! $hit['title'] !!}</span>
                            @if ($hit['meta'] !== [])
                                <x-meta :items="$hit['meta']" :tone="$onDark ? 'ivory' : 'evergreen'" class="search-results__meta" />
                            @endif
                            @if ($hit['excerpt'] !== '' && ! $compact)
                                <span class="search-results__excerpt">{!! $hit['excerpt'] !!}</span>
                            @endif
                            @if ($hit['members'])
                                <span class="search-results__members smallcaps">{{ \App\Constants\TeachingAccess::MEMBERS_NOTE }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ol>
            @if ($group['more'] > 0)
                <p class="search-results__more"><a href="{{ $group['url'] }}" class="link-draw smallcaps">{{ $group['more'] }} more in {{ $group['label'] }}</a></p>
            @endif
        </section>
    @endforeach
@endif
