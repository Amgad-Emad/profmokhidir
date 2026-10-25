{{--
    /privacy: what the site's own analytics records, in plain language. Served only while
    analytics is switched on (PrivacyController); the footer links here only then. The words
    come from Admin › Pages › Privacy; the retention numbers stay live through placeholders.
--}}
@inject('pageCopy', 'App\Services\PageContentService')
<x-layouts.public :title="$pageCopy->text('privacy', 'label')" :description="\App\Constants\PageDefinitions\SiteBlocks::PRIVACY_DESCRIPTION">
    <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28" aria-labelledby="section-title">
        <div class="mx-auto max-w-3xl">
            <x-section-header :label="$pageCopy->text('privacy', 'label')" :title="$pageCopy->text('privacy', 'heading')" level="1" id="section-title" />

            <div class="page-prose mt-12 flex flex-col gap-6 text-lg font-normal" data-rise data-privacy-analytics>
                {{ $pageCopy->html('privacy', 'body', [':idle_seconds' => $idleSeconds, ':raw_days' => $rawDays, ':rollup_months' => $rollupMonths]) }}
            </div>
        </div>
    </section>
</x-layouts.public>
