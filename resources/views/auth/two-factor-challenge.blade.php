@use('App\Constants\AuthScreens')
@php $recovery = $errors->has('recovery_code'); @endphp
<x-layouts.guest :screen="AuthScreens::TWO_FACTOR" :intro="false" :error-targets="['code' => 'code-1']">
    <div x-data="authChallenge({ recovery: {{ $recovery ? 'true' : 'false' }} })" data-two-factor>
        <p class="t-body -mt-2 mb-8 text-ash" x-show="!recovery" @if ($recovery) x-cloak @endif>{{ AuthScreens::screen(AuthScreens::TWO_FACTOR)['intro'] }}</p>
        <p class="t-body -mt-2 mb-8 text-ash" x-show="recovery" @unless ($recovery) x-cloak @endunless>{{ AuthScreens::TWO_FACTOR_RECOVERY_INTRO }}</p>

        <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-8" x-data="authForm()" x-on:submit="submit($event)" data-auth-form>
            @csrf
            <div x-show="!recovery" @if ($recovery) x-cloak @endif>
                <x-auth.code-input name="code" label="Authentication code" :autofocus="! $recovery" :hidden="['x-bind:disabled' => 'recovery']" />
            </div>
            <div x-show="recovery" @unless ($recovery) x-cloak @endunless>
                <x-auth.field name="recovery_code" label="Recovery code" required :autocomplete="AuthScreens::AUTOCOMPLETE_ONE_TIME_CODE" :autofocus="$recovery" :input="['x-bind:disabled' => '!recovery', 'x-ref' => 'recovery', 'spellcheck' => 'false', 'autocapitalize' => 'off']" />
            </div>

            <x-button variant="primary" class="w-full" :busy="AuthScreens::screen(AuthScreens::TWO_FACTOR)['busy']">{{ AuthScreens::screen(AuthScreens::TWO_FACTOR)['action'] }}</x-button>

            <p class="text-center">
                <button type="button" class="link-hairline" x-on:click="toggle()" x-text="recovery ? @js(AuthScreens::USE_AUTHENTICATOR_CODE) : @js(AuthScreens::USE_RECOVERY_CODE)" data-two-factor-switch>{{ $recovery ? AuthScreens::USE_AUTHENTICATOR_CODE : AuthScreens::USE_RECOVERY_CODE }}</button>
            </p>
        </form>
    </div>
</x-layouts.guest>
