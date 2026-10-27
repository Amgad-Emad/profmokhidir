{{--
    The one field anatomy (App\Services\FormFieldService::describe gives `field`): the label in
    the label style with "· Required" or "· Optional" beside it in the muted hint style (part of the
    field's description, so screen readers hear it too), the hint under it, the control (the slot),
    then the counter and the error. The error paragraph is always present (empty
    until needed) so resources/js/forms.js can fill it on blur. `as="fieldset"` turns the label
    into a legend for groups (radio groups, the code boxes); `hide-label` keeps it for screen
    readers only (repeated rows that a visible heading already names).
--}}
@props(['field', 'as' => 'div', 'hideLabel' => false, 'counterText' => null])
@php
    $isGroup = $as === 'fieldset';
    $labelClasses = ['t-label', 'form-label', 'form-label--hidden' => $hideLabel];
@endphp
<{{ $as }} {{ $attributes->class(['form-field', 'form-fieldset' => $isGroup, 'form-field--bare' => $hideLabel && $field['hint'] === '' && $field['requirement'] === null]) }} data-form-field{!! $isGroup && $field['describedBy'] !== '' ? ' aria-describedby="'.e($field['describedBy']).'"' : '' !!}>
    @php
        $requirement = $field['requirement'] === null ? '' : '<span id="'.e($field['requirementId']).'" class="form-requirement'.($hideLabel ? ' form-label--hidden' : '').'"><span aria-hidden="true">'.e(trim(\App\Constants\FormControls::HINT_SEPARATOR)).'</span> '.e($field['requirement']).'</span>';
    @endphp
    @if ($isGroup)
        <legend id="{{ $field['id'] }}-label" @class($labelClasses)>{{ $field['label'] }}{!! $requirement !!}</legend>
    @else
        <label id="{{ $field['id'] }}-label" for="{{ $field['id'] }}" @class($labelClasses)>{{ $field['label'] }}</label>{!! $requirement !!}
    @endif
    @if ($field['hint'] !== '')
        <p id="{{ $field['hintId'] }}" class="form-hint">{{ $field['hint'] }}</p>
    @endif
    <div class="form-field__control">{{ $slot }}</div>
    @if ($field['counter'])
        <p id="{{ $field['countId'] }}" class="form-message form-counter" data-form-counter data-max="{{ $field['maxlength'] }}" data-format="{{ __(\App\Constants\FormControls::COUNTER_FORMAT) }}" aria-live="off">{{ $counterText ?? __(\App\Constants\FormControls::COUNTER_FORMAT, ['count' => '0', 'max' => (string) $field['maxlength']]) }}</p>
    @endif
    <p id="{{ $field['errorId'] }}" class="form-message form-error" data-form-error{!! $field['invalid'] ? ' role="alert"' : '' !!}>@if ($field['invalid'])<x-icon name="warning" class="mt-1 size-4 shrink-0" /><span>{{ $field['error'] }}</span>@endif</p>
</{{ $as }}>
