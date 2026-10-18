<x-layouts.guest title="Verify your email" intro="Before continuing, please confirm your email address using the link we sent you. Did not receive it? We can send another.">
    @if (session('status') === 'verification-link-sent')
        <p class="mb-6 rounded-card border border-stone bg-linen px-4 py-3 text-sm" role="status">A new verification link has been sent to your email address.</p>
    @endif

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-button>Resend verification email</x-button>
        </form>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-bronze underline-offset-4 hover:underline">Sign out</button>
        </form>
    </div>
</x-layouts.guest>
