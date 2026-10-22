@php
    use App\Constants\AuthStatus;
    use App\Constants\StatusTone;
    use App\Constants\ThemeSettings;

    $layout = $isStaff ? 'layouts.admin' : 'layouts.public';
    $status = session('status');
@endphp
@inject('themes', 'App\Services\ThemeService')
<x-dynamic-component :component="$layout" title="Your account">
    <div @class(['mx-auto w-full max-w-3xl', 'px-4 py-16 sm:px-6' => ! $isStaff])>
        <header class="flex flex-col gap-2">
            <p class="t-label text-accent-text">Settings</p>
            <h1 class="t-heading">Your account</h1>
            <p class="max-w-2xl text-base font-light text-ink-muted">Update your name and email, change your password, choose the appearance and manage two-factor authentication.</p>
        </header>

        @if ($status && AuthStatus::isValid($status))
            <x-alert :tone="AuthStatus::tone($status)" class="mt-8" data-status="{{ $status }}">{{ AuthStatus::message($status) }}</x-alert>
        @endif

        {{-- Profile --}}
        <section class="mt-12 rounded-card border border-rule bg-surface-panel p-6 sm:p-8" aria-labelledby="profile-heading">
            <h2 id="profile-heading" class="font-display text-3xl font-light">Name and email</h2>
            <p class="mt-2 text-sm font-light text-ink-muted">Changing your email address will ask you to verify it again.</p>

            <form method="POST" action="{{ route('user-profile-information.update') }}" class="mt-6 space-y-6">
                @csrf
                @method('PUT')
                <x-input name="name" label="Name" :value="$user->name" required autocomplete="name" bag="updateProfileInformation" />
                <x-input name="email" label="Email" type="email" :value="$user->email" required autocomplete="email" bag="updateProfileInformation" />
                <x-button>Save details</x-button>
            </form>
        </section>

        {{-- Password --}}
        <section class="mt-8 rounded-card border border-rule bg-surface-panel p-6 sm:p-8" aria-labelledby="password-heading">
            <h2 id="password-heading" class="font-display text-3xl font-light">Password</h2>
            <p class="mt-2 text-sm font-light text-ink-muted">Use a long passphrase you do not use anywhere else.</p>

            <form method="POST" action="{{ route('user-password.update') }}" class="mt-6 space-y-6">
                @csrf
                @method('PUT')
                <x-input name="current_password" label="Current password" type="password" required autocomplete="current-password" bag="updatePassword" />
                <x-input name="password" label="New password" type="password" required autocomplete="new-password" bag="updatePassword" />
                <x-input name="password_confirmation" label="Confirm new password" type="password" required autocomplete="new-password" bag="updatePassword" />
                <x-button>Change password</x-button>
            </form>
        </section>

        {{-- Appearance --}}
        @php $themeOptions = $themes->options($themes->current(request())); @endphp
        <section id="appearance" class="mt-8 rounded-card border border-rule bg-surface-panel p-6 sm:p-8" aria-labelledby="appearance-heading" data-theme-section>
            <h2 id="appearance-heading" class="font-display text-3xl font-light">{{ ThemeSettings::LABEL }}</h2>
            <p class="mt-2 text-sm font-light text-ink-muted">{{ ThemeSettings::ACCOUNT_INTRO }}</p>

            <form method="POST" action="{{ route(ThemeSettings::ROUTE) }}" class="mt-6" data-theme-form>
                @csrf
                @method('PATCH')
                <fieldset>
                    <legend class="sr-only">{{ ThemeSettings::LABEL }}</legend>
                    <div class="grid gap-3 sm:grid-cols-3">
                        @foreach ($themeOptions as $option)
                            <label class="theme-option" data-theme-choice="{{ $option['value'] }}">
                                <input type="radio" name="{{ ThemeSettings::FIELD }}" value="{{ $option['value'] }}" class="theme-option__input sr-only" @checked($option['current']) data-theme-radio>
                                <span class="theme-option__body">
                                    <x-icon :name="$option['icon']" class="theme-option__icon size-5" />
                                    <span class="theme-option__label">{{ $option['label'] }}</span>
                                    <span class="theme-option__description">{{ $option['description'] }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <div class="mt-6">
                    <x-button>Save appearance</x-button>
                </div>
            </form>
        </section>

        {{-- Two-factor --}}
        <section id="two-factor" class="mt-8 rounded-card border border-rule bg-surface-panel p-6 sm:p-8" aria-labelledby="two-factor-heading" data-two-factor="{{ $twoFactor['confirmed'] ? 'confirmed' : ($twoFactor['pending'] ? 'pending' : 'off') }}">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h2 id="two-factor-heading" class="font-display text-3xl font-light">Two-factor authentication</h2>
                @if ($twoFactor['confirmed'])
                    <x-admin.status-badge :tone="StatusTone::Cypress" label="On" />
                @elseif ($twoFactor['pending'])
                    <x-admin.status-badge :tone="StatusTone::Bronze" label="Finish setup" />
                @else
                    <x-admin.status-badge :tone="StatusTone::Ash" label="Off" />
                @endif
            </div>
            <p class="mt-2 text-sm font-light text-ink-muted">
                When enabled, signing in also asks for a six-digit code from an authenticator app such as Google Authenticator, 1Password or Authy.
                @if ($twoFactor['required'])
                    Two-factor authentication is required for administrator accounts.
                @endif
            </p>

            @error('code', 'confirmTwoFactorAuthentication')
                <x-alert :tone="StatusTone::Bronze" role="alert" class="mt-6">{{ $message }}</x-alert>
            @enderror

            @if (! $twoFactor['enabled'])
                <form method="POST" action="{{ route('two-factor.enable') }}" class="mt-6">
                    @csrf
                    <x-button>Enable two-factor authentication</x-button>
                </form>
            @endif

            @if ($twoFactor['pending'])
                <div class="mt-6 grid gap-8 sm:grid-cols-[auto_1fr] sm:items-start">
                    <div class="qr-code inline-block rounded-card border border-rule bg-white p-3" aria-label="QR code for your authenticator app">
                        {!! $twoFactor['qr_code'] !!}
                    </div>
                    <div class="space-y-4">
                        <p class="text-base font-light">Scan the code with your authenticator app, or enter this key by hand:</p>
                        <p class="font-mono text-sm tracking-wide break-all" data-setup-key>{{ $twoFactor['setup_key'] }}</p>
                        <form method="POST" action="{{ route('two-factor.confirm') }}" class="space-y-4">
                            @csrf
                            <x-input name="code" label="Code from your app" inputmode="numeric" autocomplete="one-time-code" required bag="confirmTwoFactorAuthentication" />
                            <div class="flex flex-wrap items-center gap-4">
                                <x-button>Confirm and turn on</x-button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            @if ($twoFactor['confirmed'])
                <details class="mt-6 rounded-card border border-rule bg-surface-raised p-4" data-recovery-codes>
                    <summary class="cursor-pointer font-medium">Recovery codes</summary>
                    <p class="mt-2 text-sm font-light text-ink-muted">Each code signs you in once if you lose your authenticator. Keep them somewhere safe.</p>
                    <ul class="mt-4 grid gap-2 font-mono text-sm sm:grid-cols-2">
                        @foreach ($twoFactor['recovery_codes'] as $code)
                            <li>{{ $code }}</li>
                        @endforeach
                    </ul>
                    <form method="POST" action="{{ route('two-factor.recovery-codes') }}" class="mt-4">
                        @csrf
                        <x-button variant="outline">Generate new codes</x-button>
                    </form>
                </details>
            @endif

            @if ($twoFactor['enabled'])
                <form method="POST" action="{{ route('two-factor.disable') }}" class="mt-6">
                    @csrf
                    @method('DELETE')
                    <x-button variant="outline">{{ $twoFactor['pending'] ? 'Cancel setup' : 'Turn off two-factor authentication' }}</x-button>
                    @if ($twoFactor['required'] && $twoFactor['confirmed'])
                        <p class="mt-2 text-sm font-light text-ink-muted">Turning it off will lock you out of the dashboard until it is set up again.</p>
                    @endif
                </form>
            @endif
        </section>
    </div>
</x-dynamic-component>
