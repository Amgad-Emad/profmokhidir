{{--
    Opens long prose with a three-line Cormorant initial on its first paragraph (CSS
    ::first-letter, so it works on HTML from the database). `tone` is the ink:
    "evergreen" on ivory (default) or "ivory" on evergreen, where the initial is champagne.
--}}
@props(['tone' => 'evergreen'])
<div {{ $attributes->class(['drop-cap', 'drop-cap--ivory' => $tone === 'ivory']) }}>{{ $slot }}</div>
