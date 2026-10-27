@use('App\Constants\AuthScreens')
<x-layouts.guest :screen="AuthScreens::VERIFY_EMAIL">
    <form novalidate method="POST" action="{{ route('verification.send') }}" class="space-y-8" x-data="authForm()" x-on:submit="submit($event)" data-auth-form>
        @csrf
        <x-button variant="primary" class="w-full" :busy="AuthScreens::screen(AuthScreens::VERIFY_EMAIL)['busy']">{{ AuthScreens::screen(AuthScreens::VERIFY_EMAIL)['action'] }}</x-button>
    </form>
    <form novalidate method="POST" action="{{ route('logout') }}" class="mt-6 text-center">
        @csrf
        <button type="submit" class="link-hairline">{{ __(AuthScreens::SIGN_OUT) }}</button>
    </form>
</x-layouts.guest>
