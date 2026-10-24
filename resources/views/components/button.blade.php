{{--
    The button: the one component for every action in the site, the chat and the admin
    (App\Services\ButtonService, resources/css/buttons.css, colours from the .btn token table in
    tokens.css). Variants (App\Constants\ButtonVariant): "primary" the solid pill for the one main
    action of a form or screen (evergreen on ivory, ivory inside an .on-dark band); "secondary" the
    outline pill (the default); "quiet" underlined text (Back, Cancel, row actions that remove nothing);
    "icon" a tool without a visible label (give it an aria-label or sr-only text); "danger" the
    outline pill in danger red for every destructive action (Move to trash, Remove, Reject, Suspend,
    Delete message; delete actions carry the trash icon); "danger-filled" the solid red pill, only for
    the final confirm button inside a confirmation dialog (x-admin.confirm-dialog). `size` is md (default), sm or round (a 44px circle
    for an icon in any variant). `on-dark` marks the button itself as a dark-band region. `busy` is
    the label shown while the form submits (resources/js/auth.js sets aria-busy). With `href` it is a
    link. Never pass colour utilities: the variant owns every state's colours.
--}}
@props(['variant' => 'secondary', 'size' => null, 'type' => 'submit', 'href' => null, 'busy' => null, 'onDark' => false])
@inject('buttons', 'App\Services\ButtonService')
@php
    $buttons->assertNoColourUtilities($attributes->get('class'));
    $classes = $buttons->classes($variant, $size, (bool) $onDark);
@endphp
@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes, 'data-busy-label' => $busy]) }}>{{ $slot }}</button>
@endif
