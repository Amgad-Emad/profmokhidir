{{--
    A calendar date (the browser's own date picker, which reads the locale's order and works
    with the keyboard), stored as Y-m-d. `min` and `max` bound it; a date outside them, or one
    the browser cannot read, gets "Enter a date as day, month and year." on blur. Takes every
    prop of <x-form.input>.
--}}
@props(['label', 'hint' => null, 'error' => null])
<x-form.input type="date" kind="date" :label="$label" :hint="$hint" :error="$error" {{ $attributes }} />
