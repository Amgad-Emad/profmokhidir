<x-layouts.guest title="Choose a new password">
    <form method="POST" action="{{ route('password.update') }}" class="space-y-6">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <x-input name="email" label="Email" type="email" :value="$request->email" required autocomplete="username" />
        <x-input name="password" label="New password" type="password" required autocomplete="new-password" />
        <x-input name="password_confirmation" label="Confirm new password" type="password" required autocomplete="new-password" />
        <x-button>Save password</x-button>
    </form>
</x-layouts.guest>
