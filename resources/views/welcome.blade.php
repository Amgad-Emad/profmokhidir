<x-layouts.public>
    <section class="brass-glow on-dark bg-evergreen text-champagne">
        <div class="mx-auto flex max-w-7xl flex-col items-start gap-10 px-4 py-28 sm:px-6 lg:py-40">
            <p class="label animate-rise text-brass">Teacher, scholar and mentor</p>
            <h1 class="animate-rise text-heading sm:text-display [animation-delay:120ms]">Professor<br>Mohamed Osman Khidir</h1>
            <p class="animate-rise max-w-2xl text-quote font-display italic [animation-delay:240ms]">
                &ldquo;Knowledge is the light we pass on.&rdquo;
            </p>
            <div class="animate-rise flex flex-wrap gap-4 [animation-delay:360ms]">
                <x-button href="{{ url('/') }}" variant="filled-light">About Prof. Mokhidir</x-button>
                <x-button href="{{ route('login') }}" variant="outline-light">Sign in</x-button>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-24 sm:px-6">
        <p class="label text-bronze">Site under construction</p>
        <h2 class="mt-4 text-heading">A life devoted to knowledge and to those who sought it.</h2>
        <p class="mt-6 max-w-2xl text-ash">
            The biography, CV, publications, awards, videos, pictures, teaching materials, commentary,
            scientific community and tributes sections will appear here as they are published.
        </p>
    </section>
</x-layouts.public>
