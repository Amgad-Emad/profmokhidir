{{--
    /privacy: what the site's own analytics records, in plain language. Served only while
    analytics is switched on (PrivacyController); the footer links here only then.
--}}
<x-layouts.public title="Privacy" description="How this site counts its readers: first-party, without cookies, without personal data.">
    <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28" aria-labelledby="section-title">
        <div class="mx-auto max-w-3xl">
            <x-section-header label="Privacy" title="How this site counts its readers" level="1" id="section-title" />

            <div class="mt-12 flex flex-col gap-6 text-lg font-light" data-rise data-privacy-analytics>
                <p>To learn which pages are read, this site keeps its own simple visitor count. It is run by the site itself: no other company sees it, and nothing is shared or sold.</p>

                <h2 class="t-quote mt-6">What is counted</h2>
                <ul class="flex list-disc flex-col gap-2 pl-6">
                    <li>The page opened, and the site it was reached from (only its name, such as a search engine).</li>
                    <li>How long the page was read: the time the tab was in view while you scrolled or used the page within the last {{ $idleSeconds }} seconds, and how far down you scrolled.</li>
                    <li>The country you are in, worked out from your internet address, which is then forgotten. Nothing more precise than the country is kept.</li>
                    <li>Whether you use a phone, tablet or computer, and the name of the browser.</li>
                </ul>

                <h2 class="t-quote mt-6">What is not</h2>
                <ul class="flex list-disc flex-col gap-2 pl-6">
                    <li>No cookies are set and nothing is stored in your browser for this count.</li>
                    <li>No internet address, name, email or other personal detail is stored. Visits on the same day are told apart by a scrambled code that changes every day and cannot be turned back into anything about you.</li>
                    <li>If your browser asks sites not to track you (Do Not Track or Global Privacy Control), nothing is counted at all.</li>
                </ul>

                <h2 class="t-quote mt-6">How long it is kept</h2>
                <p>The record of each page view is deleted after {{ $rawDays }} days. After that only daily totals remain (for example, how many people read a page on a given day), and those are deleted after {{ $rollupMonths }} months.</p>
            </div>
        </div>
    </section>
</x-layouts.public>
