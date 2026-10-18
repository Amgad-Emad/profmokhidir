<footer class="site-footer on-dark bg-evergreen text-champagne">
    <div class="mx-auto max-w-7xl px-4 pb-10 pt-20 sm:px-6">
        <div class="grid gap-14 lg:grid-cols-[minmax(0,1fr)_auto] lg:gap-24">
            <div class="flex flex-col items-start gap-10">
                <x-brand.lockup variant="stacked" :href="route('home')" />
                <p class="t-quote max-w-xl">{{ $closingLine }}</p>
            </div>
            <nav aria-label="Sections">
                <p class="t-label text-brass">Sections</p>
                <ul class="mt-6 grid grid-cols-2 gap-x-10 gap-y-3 sm:grid-cols-3 lg:grid-cols-2">
                    @foreach ($sections as $item)
                        <li>
                            <a href="{{ $item['url'] }}" class="footer-link" @if ($item['active']) aria-current="page" @endif>{{ $item['label'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </div>
        <div class="mt-16 flex flex-col gap-4 border-t border-cypress pt-8 sm:flex-row sm:items-center sm:justify-between">
            <p class="t-label text-brass">{{ $domain }}</p>
            <p class="text-sm font-light text-champagne/80">&copy; {{ date('Y') }} Professor Mohamed Osman Khidir. All rights reserved.</p>
        </div>
    </div>
</footer>
