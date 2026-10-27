@use('App\Constants\AuthScreens')
<x-layouts.guest :screen="AuthScreens::FORGOT_PASSWORD">
    <form novalidate method="POST" action="{{ route('password.email') }}" class="space-y-8" x-data="authForm()" x-on:submit="submit($event)" data-auth-form>
        @csrf
        <x-form.input name="email" :label="__('Email')" type="email" required :autocomplete="AuthScreens::AUTOCOMPLETE_USERNAME" autofocus />
        <x-button variant="primary" class="w-full" :busy="AuthScreens::screen(AuthScreens::FORGOT_PASSWORD)['busy']">{{ AuthScreens::screen(AuthScreens::FORGOT_PASSWORD)['action'] }}</x-button>
        <p class="text-center"><a href="{{ route('login') }}" class="link-hairline">{{ __('Back to sign in') }}</a></p>
    </form>
</x-layouts.guest>
