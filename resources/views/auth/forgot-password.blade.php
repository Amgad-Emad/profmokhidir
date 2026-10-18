<x-layouts.guest title="Reset password" intro="Enter your email address and we will send you a link to choose a new password.">
    <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
        @csrf
        <x-input name="email" label="Email" type="email" required autocomplete="username" autofocus />
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <x-button>Email reset link</x-button>
            <a href="{{ route('login') }}" class="text-sm text-bronze underline-offset-4 hover:underline">Back to sign in</a>
        </div>
    </form>
</x-layouts.guest>
