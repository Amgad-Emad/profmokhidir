@use('App\Constants\AuthScreens')
<x-layouts.guest :screen="AuthScreens::RESET_PASSWORD">
    <form method="POST" action="{{ route('password.update') }}" class="space-y-8" x-data="authForm()" x-on:submit="submit($event)" data-auth-form>
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <x-auth.field name="email" label="Email" type="email" :value="$request->email" required :autocomplete="AuthScreens::AUTOCOMPLETE_USERNAME" />
        <x-auth.password-field name="password" label="New password" :autocomplete="AuthScreens::AUTOCOMPLETE_NEW_PASSWORD" :strength="true" autofocus />
        <x-auth.password-field name="password_confirmation" label="Confirm new password" :autocomplete="AuthScreens::AUTOCOMPLETE_NEW_PASSWORD" />
        <x-button variant="primary" class="w-full" :busy="AuthScreens::screen(AuthScreens::RESET_PASSWORD)['busy']">{{ AuthScreens::screen(AuthScreens::RESET_PASSWORD)['action'] }}</x-button>
    </form>
</x-layouts.guest>
