<x-layouts.guest title="Sign in">
    <form method="POST" action="{{ route('login') }}" class="space-y-6">
        @csrf
        <x-input name="email" label="Email" type="email" required autocomplete="username" autofocus />
        <x-input name="password" label="Password" type="password" required autocomplete="current-password" />

        <label class="flex items-center gap-3 text-sm text-ash">
            <input type="checkbox" name="remember" class="size-4 rounded-sm border-stone accent-evergreen">
            Keep me signed in
        </label>

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <x-button>Sign in</x-button>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-sm text-bronze underline-offset-4 hover:underline">Forgotten your password?</a>
            @endif
        </div>
    </form>
</x-layouts.guest>
