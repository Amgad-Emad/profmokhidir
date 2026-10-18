<x-layouts.guest title="Confirm your password" intro="This is a secure area. Please confirm your password before continuing.">
    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-6">
        @csrf
        <x-input name="password" label="Password" type="password" required autocomplete="current-password" autofocus />
        <x-button>Confirm</x-button>
    </form>
</x-layouts.guest>
