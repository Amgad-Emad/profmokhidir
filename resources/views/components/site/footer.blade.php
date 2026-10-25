<footer class="site-footer on-dark bg-surface text-ink">
    <div class="mx-auto max-w-7xl px-4 pb-10 pt-20 sm:px-6">
        <div class="grid gap-14 lg:grid-cols-[minmax(0,1fr)_auto] lg:gap-24">
            <div class="flex flex-col items-start gap-10">
                <x-brand.lockup variant="stacked" :href="route('home')" />
                <p class="t-quote max-w-xl">{{ $closingLine }}</p>
            </div>
            <nav aria-label="Sections">
                <p class="t-label text-accent-text">{{ $heading }}</p>
                <ul class="mt-6 grid grid-cols-2 gap-x-10 gap-y-3 sm:grid-cols-3 lg:grid-cols-2">
                    @foreach ($sections as $item)
                        <li>
                            <a href="{{ $item['url'] }}" class="footer-link" @if ($item['active']) aria-current="page" @endif>{{ $item['label'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </div>
        <div class="mt-16 flex flex-col gap-4 border-t border-rule pt-8 sm:flex-row sm:items-center sm:justify-between">
            <p class="t-label text-accent-text">{{ $domain }}</p>
            <p class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm font-light text-ink-muted">
                @if ($privacyUrl)
                    <a href="{{ $privacyUrl }}" class="footer-link"@if (request()->routeIs(\App\Constants\Analytics::PRIVACY_ROUTE)) aria-current="page"@endif>{{ $privacyLabel }}</a>
                @endif
                <span>{{ $copyright }}</span>
            </p>
        </div>
    </div>
</footer>
