{{--
    The one field anatomy (App\Services\FormFieldService::describe gives `field`): the label in
    the label style, the hint directly under it with "Required" or "Optional" first, the control
    (the slot), then the counter and the error. The error paragraph is always present (empty
    until needed) so resources/js/forms.js can fill it on blur. `as="fieldset"` turns the label
    into a legend for groups (radio groups, the code boxes); `hide-label` keeps it for screen
    readers only (repeated rows that a visible heading already names).
--}}
@props(['field', 'as' => 'div', 'hideLabel' => false, 'counterText' => null])
@php
    $isGroup = $as === 'fieldset';
    $labelClasses = ['t-label', 'form-label', 'form-label--hidden' => $hideLabel];
@endphp
<{{ $as }} {{ $attributes->class(['form-field', 'form-fieldset' => $isGroup, 'form-field--bare' => $hideLabel && $field['hint'] === '']) }} data-form-field{!! $isGroup && $field['describedBy'] !== '' ? ' aria-describedby="'.e($field['describedBy']).'"' : '' !!}>
    @if ($isGroup)
        <legend id="{{ $field['id'] }}-label" @class($labelClasses)>{{ $field['label'] }}</legend>
    @else
        <label id="{{ $field['id'] }}-label" for="{{ $field['id'] }}" @class($labelClasses)>{{ $field['label'] }}</label>
    @endif
    @if ($field['hint'] !== '')
        <p id="{{ $field['hintId'] }}" class="form-hint">@if ($field['requirement'] !== null)<span class="form-hint__requirement">{{ $field['requirement'] }}</span>@endif{{ $field['requirement'] !== null && $field['hintText'] !== '' ? \App\Constants\FormControls::HINT_SEPARATOR : '' }}{{ $field['hintText'] }}</p>
    @endif
    <div class="form-field__control">{{ $slot }}</div>
    @if ($field['counter'])
        <p id="{{ $field['countId'] }}" class="form-message form-counter" data-form-counter data-max="{{ $field['maxlength'] }}" data-format="{{ \App\Constants\FormControls::COUNTER_FORMAT }}" aria-live="off">{{ $counterText ?? strtr(\App\Constants\FormControls::COUNTER_FORMAT, [':count' => '0', ':max' => (string) $field['maxlength']]) }}</p>
    @endif
    <p id="{{ $field['errorId'] }}" class="form-message form-error" data-form-error{!! $field['invalid'] ? ' role="alert"' : '' !!}>@if ($field['invalid'])<x-icon name="warning" class="mt-1 size-4 shrink-0" /><span>{{ $field['error'] }}</span>@endif</p>
</{{ $as }}>
