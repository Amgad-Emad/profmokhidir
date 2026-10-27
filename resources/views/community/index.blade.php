@use('App\Constants\AuthScreens')
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
                    <p class="t-label text-accent-text">{{ $copy['whoHeading'] }}</p>
                    <h2 id="who-heading" class="sr-only">{{ $copy['whoHeading'] }}</h2>
                    <p class="t-body mt-4 text-ink-muted">{{ $copy['who'] }}</p>
                </section>

                <section aria-labelledby="conduct-heading" data-rise>
                    <p class="t-label text-accent-text">{{ $copy['conductHeading'] }}</p>
                    <h2 id="conduct-heading" class="sr-only">{{ $copy['conductHeading'] }}</h2>
                    <ol class="mt-4 space-y-3 text-base font-normal text-ink-muted" data-conduct>
                        @foreach ($copy['conduct'] as $rule)
                            <li class="flex gap-4"><span class="font-mono text-sm text-accent-text">{{ app(\App\Services\LocaleService::class)->digits(sprintf('%02d', $loop->iteration)) }}</span><span>{{ $rule }}</span></li>
                        @endforeach
                    </ol>
                </section>

                <section id="privacy" aria-labelledby="privacy-heading" data-rise>
                    <p class="t-label text-accent-text">{{ $copy['privacyHeading'] }}</p>
                    <h2 id="privacy-heading" class="sr-only">{{ $copy['privacyHeading'] }}</h2>
                    <p class="mt-4 text-base font-normal text-ink-muted" data-privacy>{{ $copy['privacy'] }}</p>
                </section>
            </div>

            {{-- Signup form, or the signed-in user's standing --}}
            <div>
                @if (session(CommunityController::STATUS_KEY))
                    <x-alert tone="cypress" class="mb-8" data-status>{{ session(CommunityController::STATUS_KEY) }}</x-alert>
                @endif

                @if ($standing !== null)
                    <section class="rounded-card border border-rule bg-surface-raised p-8 sm:p-10" aria-labelledby="standing-heading" data-standing="{{ $standing['status']->value }}">
                        <x-admin.status-badge :tone="$standing['tone']" :label="$standing['status']->label()" />
                        <h2 id="standing-heading" class="mt-6 font-display text-4xl font-light leading-tight">{{ $standing['heading'] }}</h2>
                        <p class="mt-4 text-base font-normal text-ink-muted">{{ $standing['message'] }}</p>
                        <div class="mt-8 flex flex-wrap gap-4">
                            @if ($standing['chatUrl'])
                                <x-button :href="$standing['chatUrl']">{{ $copy['chatButton'] }}</x-button>
                            @endif
                            <x-button variant="quiet" :href="route(\App\Constants\AdminNavigation::ACCOUNT_ROUTE)">{{ $copy['accountButton'] }}</x-button>
                        </div>
                    </section>

                    @if ($standing['canApply'])
                        <section class="mt-10" aria-labelledby="apply-heading">
                            <p class="t-label text-accent-text">{{ $copy['applyHeading'] }}</p>
                            <h2 id="apply-heading" class="sr-only">{{ $copy['applyHeading'] }}</h2>
                            <hr class="rule-brass mt-4" data-rule>
                            <form novalidate method="POST" action="{{ $applyUrl }}" class="form-style-underline mt-8 space-y-8" x-data="authForm()" x-on:submit="submit($event)" data-apply-form>
                                @csrf
                                <x-form.error-summary :targets="['throttle' => 'apply-heading']" />
                                @include('community.partials.application-fields', ['copy' => $copy])
                                <x-button variant="primary" class="w-full" :busy="__(AuthScreens::screen(AuthScreens::COMMUNITY_SIGNUP)['busy'])">{{ __(AuthScreens::screen(AuthScreens::COMMUNITY_SIGNUP)['action']) }}</x-button>
                            </form>
                        </section>
                    @endif
                @else
                    <section class="lg:ps-8" aria-labelledby="apply-heading">
                        <p class="t-label text-accent-text">{{ $copy['applyHeading'] }}</p>
                        <h2 id="apply-heading" class="mt-3 font-display text-4xl font-light leading-tight">{{ __(AuthScreens::screen(AuthScreens::COMMUNITY_SIGNUP)['title']) }}</h2>
                        <p class="t-body mt-4 text-ink-muted">{{ $copy['applyIntro'] }} {!! __('Already a member? :link.', ['link' => '<a href="'.e($loginUrl).'" class="link-hairline">'.e(__('Sign in')).'</a>']) !!}</p>
                        <hr class="rule-brass mt-6" data-rule>

                        <form novalidate method="POST" action="{{ $applyUrl }}" class="form-style-underline mt-8 space-y-8" x-data="authForm()" x-on:submit="submit($event)" data-apply-form>
                            @csrf

                            <x-form.error-summary :targets="['throttle' => 'apply-heading']" />

                            <x-form.input name="name" :label="__('Your name')" required autocomplete="name" />
                            <x-form.input name="email" :label="__('Email')" type="email" required autocomplete="email" :hint="__('We will send a link to confirm it.')" />
                            <x-form.password name="password" :label="__('Password')" :autocomplete="AuthScreens::AUTOCOMPLETE_NEW_PASSWORD" :strength="true" />
                            <x-form.password name="password_confirmation" :label="__('Confirm password')" :autocomplete="AuthScreens::AUTOCOMPLETE_NEW_PASSWORD" />

                            @include('community.partials.application-fields', ['copy' => $copy])

                            <x-button variant="primary" class="w-full" :busy="__(AuthScreens::screen(AuthScreens::COMMUNITY_SIGNUP)['busy'])">{{ __(AuthScreens::screen(AuthScreens::COMMUNITY_SIGNUP)['action']) }}</x-button>
                        </form>
                    </section>
                @endif
            </div>
        </div>
    </section>
</x-layouts.public>
