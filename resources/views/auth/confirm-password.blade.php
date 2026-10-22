@use('App\Constants\AuthScreens')
<x-layouts.guest :screen="AuthScreens::CONFIRM_PASSWORD">
    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-8" x-data="authForm()" x-on:submit="submit($event)" data-auth-form>
        @csrf
        <x-auth.password-field name="password" label="Password" :autocomplete="AuthScreens::AUTOCOMPLETE_CURRENT_PASSWORD" autofocus />
        <x-button variant="primary" class="w-full" :busy="AuthScreens::screen(AuthScreens::CONFIRM_PASSWORD)['busy']">{{ AuthScreens::screen(AuthScreens::CONFIRM_PASSWORD)['action'] }}</x-button>
    </form>
</x-layouts.guest>
