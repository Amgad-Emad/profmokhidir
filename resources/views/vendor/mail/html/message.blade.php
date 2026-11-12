<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
<img src="{{ asset(\App\Constants\BrandAssets::EMAIL_LOGO) }}" width="{{ \App\Constants\BrandAssets::EMAIL_LOGO_WIDTH_PX }}" alt="{{ __(\App\Constants\BrandMark::TITLE) }}" class="brand-logo" style="display: block; width: {{ \App\Constants\BrandAssets::EMAIL_LOGO_WIDTH_PX }}px; max-width: 100%; height: auto; margin: 0 auto; border: 0;">
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
© {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
