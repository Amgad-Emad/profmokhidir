<x-layouts.guest title="Two-factor authentication">
    <div x-data="{ recovery: false }">
        <p class="text-ash" x-show="!recovery">Enter the code from your authenticator app.</p>
        <p class="text-ash" x-show="recovery" x-cloak>Enter one of your emergency recovery codes.</p>

        <form method="POST" action="{{ route('two-factor.login') }}" class="mt-6 space-y-6">
            @csrf
            <div x-show="!recovery">
                <x-input name="code" label="Authentication code" inputmode="numeric" autocomplete="one-time-code" autofocus />
            </div>
            <div x-show="recovery" x-cloak>
                <x-input name="recovery_code" label="Recovery code" autocomplete="one-time-code" />
            </div>

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <x-button>Continue</x-button>
                <button type="button" class="text-sm text-bronze underline-offset-4 hover:underline"
                        x-on:click="recovery = !recovery"
                        x-text="recovery ? 'Use an authentication code' : 'Use a recovery code'"></button>
            </div>
        </form>
    </div>
</x-layouts.guest>
