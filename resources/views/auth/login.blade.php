@use('App\Constants\AuthScreens')
<x-layouts.guest :screen="AuthScreens::LOGIN">
    <form method="POST" action="{{ route('login') }}" class="space-y-8" x-data="authForm()" x-on:submit="submit($event)" data-auth-form>
        @csrf
        <x-auth.field name="email" label="Email" type="email" required :autocomplete="AuthScreens::AUTOCOMPLETE_USERNAME" autofocus />
        <x-auth.password-field name="password" label="Password" :autocomplete="AuthScreens::AUTOCOMPLETE_CURRENT_PASSWORD" />

        <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-2">
            <x-auth.checkbox name="remember">{{ AuthScreens::REMEMBER_LABEL }}</x-auth.checkbox>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="link-hairline">{{ AuthScreens::FORGOT_LINK }}</a>
            @endif
        </div>

        <x-button variant="primary" class="w-full" :busy="AuthScreens::screen(AuthScreens::LOGIN)['busy']">{{ AuthScreens::screen(AuthScreens::LOGIN)['action'] }}</x-button>
    </form>
</x-layouts.guest>
