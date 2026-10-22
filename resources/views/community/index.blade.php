@use('App\Http\Requests\CommunitySignupRequest')
@use('App\Http\Controllers\CommunityController')
<x-layouts.public :title="$section['title']" :description="$section['summary']">
    <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28" aria-labelledby="section-title">
        <x-chapter-opening :number="$chapter" :label="$section['label']" :title="$copy['heading']" level="1" id="section-title">
            {{ $copy['intro'] }}
        </x-chapter-opening>

        <div class="mt-16 grid gap-16 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] lg:gap-20">
            {{-- What the community is, who may join, conduct and privacy --}}
            <div class="space-y-12">
                <section aria-labelledby="who-heading" data-rise>
                    <p class="t-label text-bronze">{{ $copy['whoHeading'] }}</p>
                    <h2 id="who-heading" class="sr-only">{{ $copy['whoHeading'] }}</h2>
                    <p class="t-body mt-4 text-ash">{{ $copy['who'] }}</p>
                </section>

                <section aria-labelledby="conduct-heading" data-rise>
                    <p class="t-label text-bronze">{{ $copy['conductHeading'] }}</p>
                    <h2 id="conduct-heading" class="sr-only">{{ $copy['conductHeading'] }}</h2>
                    <ol class="mt-4 space-y-3 text-base font-light text-ash" data-conduct>
                        @foreach ($copy['conduct'] as $rule)
                            <li class="flex gap-4"><span class="font-mono text-xs text-bronze">{{ sprintf('%02d', $loop->iteration) }}</span><span>{{ $rule }}</span></li>
                        @endforeach
                    </ol>
                </section>

                <section id="privacy" aria-labelledby="privacy-heading" data-rise>
                    <p class="t-label text-bronze">{{ $copy['privacyHeading'] }}</p>
                    <h2 id="privacy-heading" class="sr-only">{{ $copy['privacyHeading'] }}</h2>
                    <p class="mt-4 text-base font-light text-ash" data-privacy>{{ $copy['privacy'] }}</p>
                </section>
            </div>

            {{-- Signup form, or the signed-in user's standing --}}
            <div>
                @if (session(CommunityController::STATUS_KEY))
                    <x-alert tone="cypress" class="mb-8" data-status>{{ session(CommunityController::STATUS_KEY) }}</x-alert>
                @endif

                @if ($standing !== null)
                    <section class="rounded-card border border-stone bg-linen p-8 sm:p-10" aria-labelledby="standing-heading" data-standing="{{ $standing['status']->value }}">
                        <x-admin.status-badge :tone="$standing['tone']" :label="$standing['status']->label()" />
                        <h2 id="standing-heading" class="mt-6 font-display text-4xl font-light leading-tight">{{ $standing['heading'] }}</h2>
                        <p class="mt-4 text-base font-light text-ash">{{ $standing['message'] }}</p>
                        <div class="mt-8 flex flex-wrap gap-4">
                            @if ($standing['chatUrl'])
                                <x-button :href="$standing['chatUrl']">Open the chat</x-button>
                            @endif
                            <x-button variant="outline" :href="route(\App\Constants\AdminNavigation::ACCOUNT_ROUTE)">Your account</x-button>
                        </div>
                    </section>

                    @if ($standing['canApply'])
                        <section class="mt-10" aria-labelledby="apply-heading">
                            <p class="t-label text-bronze">{{ $copy['applyHeading'] }}</p>
                            <h2 id="apply-heading" class="sr-only">{{ $copy['applyHeading'] }}</h2>
                            <form method="POST" action="{{ $applyUrl }}" class="mt-6 space-y-8" data-apply-form>
                                @csrf
                                @include('community.partials.application-fields', ['copy' => $copy])
                                <x-button>Apply to join</x-button>
                            </form>
                        </section>
                    @endif
                @else
                    <section class="rounded-card border border-stone bg-linen p-8 sm:p-10" aria-labelledby="apply-heading">
                        <p class="t-label text-bronze">{{ $copy['applyHeading'] }}</p>
                        <h2 id="apply-heading" class="sr-only">{{ $copy['applyHeading'] }}</h2>
                        <p class="mt-4 text-base font-light text-ash">{{ $copy['applyIntro'] }} Already a member? <a href="{{ $loginUrl }}" class="text-evergreen underline decoration-brass underline-offset-4">Sign in</a>.</p>

                        <form method="POST" action="{{ $applyUrl }}" class="mt-8 space-y-8" data-apply-form>
                            @csrf

                            @error('throttle')
                                <p class="rounded-card border border-stone bg-ivory px-4 py-3 text-base font-light text-bronze" role="alert">{{ $message }}</p>
                            @enderror

                            <x-input name="name" label="Your name" required autocomplete="name" />
                            <x-input name="email" label="Email" type="email" required autocomplete="email" hint="We will send a link to confirm it." />
                            <x-input name="password" label="Password" type="password" required autocomplete="new-password" hint="At least eight characters." />
                            <x-input name="password_confirmation" label="Confirm password" type="password" required autocomplete="new-password" />

                            @include('community.partials.application-fields', ['copy' => $copy])

                            <x-button>Apply to join</x-button>
                        </form>
                    </section>
                @endif
            </div>
        </div>
    </section>
</x-layouts.public>
