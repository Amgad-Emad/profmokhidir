{{--
    A quiet, dismissible suggestion for visitors whose browser prefers Arabic, on English public pages
    only, until they choose a language or dismiss it (App\Services\LocaleService::shouldSuggest). Never a
    redirect, so crawlers and shared links always get the address they asked for.
--}}
@use('App\Constants\Locales')
@inject('locales', 'App\Services\LocaleService')
@if ($locales->shouldSuggest(request()))
    @php($href = $locales->switchUrl(Locales::AR, $locales->alternateUrl(request(), Locales::AR)))
    <aside class="locale-suggestion" lang="ar" dir="rtl" aria-label="اللغة العربية" data-locale-suggestion>
        <a href="{{ $href }}" class="locale-suggestion__link" hreflang="ar" rel="alternate nofollow" data-locale-switch>اقرأ الموقع بالعربية</a>
        <button type="button" class="locale-suggestion__close" data-locale-suggestion-dismiss="{{ Locales::SUGGESTION_COOKIE }}">
            <x-icon name="close" class="size-4" label="إغلاق" />
        </button>
    </aside>
@endif
