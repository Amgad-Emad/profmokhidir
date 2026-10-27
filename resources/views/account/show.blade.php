@inject('localeSwitch', 'App\Services\LocaleService')
@php
    use App\Constants\AuthStatus;
    use App\Constants\StatusTone;
    use App\Constants\ThemeSettings;

    $layout = $isStaff ? 'layouts.admin' : 'layouts.public';
    $status = session('status');
@endphp
<x-dynamic-component :component="$layout" :title="__('Your account')">
    <div @class(['mx-auto w-full max-w-3xl', 'px-4 py-16 sm:px-6' => ! $isStaff])>
        <header class="flex flex-col gap-2">
            <p class="t-label text-accent-text">{{ __('Settings') }}</p>
            <h1 class="t-heading">{{ __('Your account') }}</h1>
            <p class="max-w-2xl text-base font-normal text-ink-muted">{{ __('Update your name and email, change your password, choose the appearance and language, and manage two-factor authentication.') }}</p>
        </header>

        @if ($status && AuthStatus::isValid($status))
            <x-alert :tone="AuthStatus::tone($status)" class="mt-8" data-status="{{ $status }}">{{ AuthStatus::message($status) }}</x-alert>
        @endif

        {{-- Profile --}}
        <section class="mt-12 rounded-card border border-rule bg-surface-panel p-6 sm:p-8" aria-labelledby="profile-heading">
            <h2 id="profile-heading" class="font-display text-3xl font-light">{{ __('Name and email') }}</h2>
            <p class="mt-2 text-sm font-normal text-ink-muted">{{ __('Changing your email address will ask you to verify it again.') }}</p>

            <form novalidate method="POST" action="{{ route('user-profile-information.update') }}" class="form-grid mt-6">
                @csrf
                @method('PUT')
                <x-form.input name="name" :label="__('Name')" :value="$user->name" required autocomplete="name" bag="updateProfileInformation" />
                <x-form.input name="email" :label="__('Email')" type="email" :value="$user->email" required autocomplete="email" bag="updateProfileInformation" />
                <x-button>{{ __('Save details') }}</x-button>
            </form>
        </section>

        {{-- Password --}}
        <section class="mt-8 rounded-card border border-rule bg-surface-panel p-6 sm:p-8" aria-labelledby="password-heading">
            <h2 id="password-heading" class="font-display text-3xl font-light">{{ __('Password') }}</h2>
            <p class="mt-2 text-sm font-normal text-ink-muted">{{ __('Use a long passphrase you do not use anywhere else.') }}</p>

            <form novalidate method="POST" action="{{ route('user-password.update') }}" class="form-grid mt-6">
                @csrf
                @method('PUT')
                <x-form.password name="current_password" :label="__('Current password')" autocomplete="current-password" bag="updatePassword" />
                <x-form.password name="password" :label="__('New password')" autocomplete="new-password" :strength="true" bag="updatePassword" />
                <x-form.password name="password_confirmation" :label="__('Confirm new password')" autocomplete="new-password" bag="updatePassword" />
                <x-button>{{ __('Change password') }}</x-button>
            </form>
        </section>

        {{-- Appearance --}}
        <section id="appearance" class="mt-8 rounded-card border border-rule bg-surface-panel p-6 sm:p-8" aria-labelledby="appearance-heading" data-theme-section>
            <div class="flex items-start justify-between gap-6">
                <div>
                    <h2 id="appearance-heading" class="font-display text-3xl font-light">{{ __(ThemeSettings::LABEL) }}</h2>
                    <p class="mt-2 max-w-xl text-sm font-normal text-ink-muted">{{ __(ThemeSettings::ACCOUNT_INTRO) }}</p>
                </div>
                <x-theme.switch id="theme-switch-account" class="shrink-0" />
            </div>
        </section>

        {{-- Language: links to the language switch, which keeps the choice on the account and in a cookie. --}}
        <section id="language" class="mt-8 rounded-card border border-rule bg-surface-panel p-6 sm:p-8" aria-labelledby="language-heading" data-language-section>
            <div class="flex flex-wrap items-start justify-between gap-6">
                <div>
                    <h2 id="language-heading" class="font-display text-3xl font-light">{{ __('Language') }}</h2>
                    <p class="mt-2 max-w-xl text-sm font-normal text-ink-muted">{{ __('The language of the dashboard, the chat, the sign-in screens and the emails we send you.') }}</p>
                </div>
                <ul class="flex shrink-0 flex-wrap gap-3" role="list" aria-label="{{ __('Language') }}">
                    @foreach (\App\Constants\Locales::all() as $locale)
                        @php $active = app()->getLocale() === $locale; @endphp
                        <li>
                            <x-button :href="$localeSwitch->switchUrl($locale, route(\App\Constants\AdminNavigation::ACCOUNT_ROUTE))" :variant="$active ? 'primary' : 'secondary'" size="sm"
                                      lang="{{ \App\Constants\Locales::html($locale) }}" dir="{{ \App\Constants\Locales::dir($locale) }}"
                                      :aria-current="$active ? 'true' : null" data-locale-option="{{ $locale }}">{{ \App\Constants\Locales::native($locale) }}</x-button>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        {{-- Two-factor --}}
        <section id="two-factor" class="mt-8 rounded-card border border-rule bg-surface-panel p-6 sm:p-8" aria-labelledby="two-factor-heading" data-two-factor="{{ $twoFactor['confirmed'] ? 'confirmed' : ($twoFactor['pending'] ? 'pending' : 'off') }}">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h2 id="two-factor-heading" class="font-display text-3xl font-light">{{ __('Two-factor authentication') }}</h2>
                @if ($twoFactor['confirmed'])
                    <x-admin.status-badge :tone="StatusTone::Cypress" :label="__('On')" />
                @elseif ($twoFactor['pending'])
                    <x-admin.status-badge :tone="StatusTone::Bronze" :label="__('Finish setup')" />
                @else
                    <x-admin.status-badge :tone="StatusTone::Ash" :label="__('Off')" />
                @endif
            </div>
            @if ($twoFactor['recommended'])
                <p class="t-label mt-3 text-accent-text" data-two-factor-recommended>{{ __('Recommended for administrator accounts') }}</p>
            @endif
            <p class="mt-2 text-sm font-normal text-ink-muted">
                {{ __('When enabled, signing in also asks for a six-digit code from an authenticator app such as Google Authenticator, 1Password or Authy.') }}
            </p>

            @error('code', 'confirmTwoFactorAuthentication')
                <x-alert :tone="StatusTone::Danger" role="alert" class="mt-6">{{ $message }}</x-alert>
            @enderror

            @if (! $twoFactor['enabled'])
                <form novalidate method="POST" action="{{ route('two-factor.enable') }}" class="mt-6">
                    @csrf
                    <x-button>{{ __('Enable two-factor authentication') }}</x-button>
                </form>
            @endif

            @if ($twoFactor['pending'])
                <div class="mt-6 grid gap-8 sm:grid-cols-[auto_1fr] sm:items-start">
                    <div class="qr-code inline-block rounded-card border border-rule bg-white p-3" aria-label="{{ __('QR code for your authenticator app') }}">
                        {!! $twoFactor['qr_code'] !!}
                    </div>
                    <div class="space-y-4">
                        <p class="text-base font-normal">{{ __('Scan the code with your authenticator app, or enter this key by hand:') }}</p>
                        <p class="font-mono text-sm tracking-wide break-all" dir="ltr" data-setup-key>{{ $twoFactor['setup_key'] }}</p>
                        <form novalidate method="POST" action="{{ route('two-factor.confirm') }}" class="space-y-4">
                            @csrf
                            <x-form.otp name="code" :label="__('Code from your app')" :autofocus="false" bag="confirmTwoFactorAuthentication" />
                            <div class="flex flex-wrap items-center gap-4">
                                <x-button>{{ __('Confirm and turn on') }}</x-button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            @if ($twoFactor['confirmed'])
                <details class="mt-6 rounded-card border border-rule bg-surface-raised p-4" data-recovery-codes>
                    <summary class="cursor-pointer font-medium">{{ __('Recovery codes') }}</summary>
                    <p class="mt-2 text-sm font-normal text-ink-muted">{{ __('Each code signs you in once if you lose your authenticator. Keep them somewhere safe.') }}</p>
                    <ul class="mt-4 grid gap-2 font-mono text-sm sm:grid-cols-2" dir="ltr">
                        @foreach ($twoFactor['recovery_codes'] as $code)
                            <li>{{ $code }}</li>
                        @endforeach
                    </ul>
                    <form novalidate method="POST" action="{{ route('two-factor.recovery-codes') }}" class="mt-4">
                        @csrf
                        <x-button variant="quiet">{{ __('Generate new codes') }}</x-button>
                    </form>
                </details>
            @endif

            @if ($twoFactor['enabled'])
                <form novalidate method="POST" action="{{ route('two-factor.disable') }}" class="mt-6">
                    @csrf
                    @method('DELETE')
                    <x-button variant="quiet">{{ $twoFactor['pending'] ? __('Cancel setup') : __('Turn off two-factor authentication') }}</x-button>
                </form>
            @endif
        </section>

        {{-- Sign out --}}
        <section class="mt-8 flex flex-wrap items-center justify-between gap-4 border-t border-rule pt-8" aria-labelledby="sign-out-heading" data-account-sign-out>
            <div>
                <h2 id="sign-out-heading" class="font-display text-3xl font-light">{{ __(\App\Constants\AuthScreens::SIGN_OUT) }}</h2>
                <p class="mt-2 text-sm font-normal text-ink-muted">{{ __('Signed in as :email.', ['email' => $user->email]) }}</p>
            </div>
            <x-account.sign-out />
        </section>
    </div>
</x-dynamic-component>
