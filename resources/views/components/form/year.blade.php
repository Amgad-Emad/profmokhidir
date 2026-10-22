{{--
    A four-digit year: a numeric keypad on phones, no spinner, and a range (from
    App\Constants\FormControls::YEAR_MIN to next year unless `min`/`max` say otherwise) that
    forms.js checks on blur with the message "Enter a year between 1900 and …". Takes every
    prop of <x-form.input>.
--}}
@props(['min' => \App\Constants\FormControls::YEAR_MIN, 'max' => null, 'control' => [], 'label', 'hint' => null, 'error' => null])
@php $max ??= \App\Constants\FormControls::yearMax(); @endphp
<x-form.input
    :label="$label"
    :hint="$hint"
    :error="$error"
    type="text"
    inputmode="numeric"
    pattern="[0-9]{4}"
    :maxlength="\App\Constants\FormControls::YEAR_DIGITS"
    autocomplete="off"
    kind="year"
    :limits="['min' => $min, 'max' => $max]"
    :control="['data-range-min' => $min, 'data-range-max' => $max, 'size' => \App\Constants\FormControls::YEAR_DIGITS + 2] + $control"
    {{ $attributes }}
/>
