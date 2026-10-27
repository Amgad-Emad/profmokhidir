{{--
    Sign out: a POST form to Fortify's logout route with the CSRF token, so it works without
    script and cannot be triggered by a link. Used in the public account menu, the chat header
    menu, the admin top bar menu and sidebar foot, and at the foot of the account page.
    By default an <x-button> (pass `variant` and `size`); inside a menu pass `item-class` to
    draw it as that menu's item, with the arrow icon. After signing out the home page shows a
    quiet notice (App\Http\Responses\LogoutResponse).
--}}
@use('App\Constants\AuthScreens')
@props(['variant' => 'secondary', 'size' => null, 'itemClass' => null, 'label' => null])
@php $label ??= __(AuthScreens::SIGN_OUT); @endphp
<form novalidate method="POST" action="{{ route('logout') }}" {{ $attributes }} data-sign-out>
    @csrf
    @if ($itemClass)
        <button type="submit" class="{{ $itemClass }}" data-leave-ok><x-icon name="arrow" class="size-4" /><span class="sign-out__label">{{ $label }}</span></button>
    @else
        <x-button :variant="$variant" :size="$size" data-leave-ok>{{ $label }}</x-button>
    @endif
</form>
