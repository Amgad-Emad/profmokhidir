{{--
    An on/off switch for a setting that takes effect as a whole (featured, shown on the home
    page): a checkbox with role="switch", the label and hint on the left, the track and the
    word "On" or "Off" on the right, so the state never rests on position or colour alone.
    Livewire passes `model`; plain forms `name` (a hidden 0 is sent when it is off).
--}}
@props([
    'name' => null,
    'model' => null,
    'live' => false,
    'label',
    'hint' => null,
    'checked' => null,
    'id' => null,
    'bag' => 'default',
    'error' => null,
])
@inject('forms', 'App\Services\FormFieldService')
@php
    $field = $forms->describe(['error' => $error, 
        'name' => $name, 'model' => $model, 'wire' => $live ? 'live' : '',
        'id' => $id, 'label' => $label, 'hint' => null, 'optional' => false, 'marker' => false, 'bag' => $bag,
    ], $errors ?? null);
    $hintId = $field['id'].'-note';
    $isChecked = $model === null && ($checked ?? (bool) old($field['key']));
    $switchAttributes = $forms->controlAttributes($field, array_merge(
        ['type' => 'checkbox', 'role' => 'switch', 'value' => $model === null ? '1' : null, 'checked' => $isChecked, 'aria-describedby' => $hint ? $hintId : null, 'data-label' => null],
    ), $attributes->except(['class'])->getAttributes());
@endphp
<div {{ $attributes->only('class')->class(['form-field']) }} data-form-field>
    <label class="form-toggle" for="{{ $field['id'] }}">
        <span class="form-toggle__text">
            <span class="block font-medium">{{ $label }}</span>
            @if ($hint)<span id="{{ $hintId }}" class="form-check__hint">{{ $hint }}</span>@endif
        </span>
        <span class="form-toggle__control">
            <span class="form-toggle__state" aria-hidden="true"><span class="form-toggle__on">{{ __(\App\Constants\FormControls::TOGGLE_ON) }}</span><span class="form-toggle__off">{{ __(\App\Constants\FormControls::TOGGLE_OFF) }}</span></span>
            @if ($model === null && $name !== null)<input type="hidden" name="{{ $name }}" value="0">@endif
            <input class="form-toggle__input" {{ $switchAttributes }}>
        </span>
    </label>
    <p id="{{ $field['errorId'] }}" class="form-message form-error" data-form-error{!! $field['invalid'] ? ' role="alert"' : '' !!}>@if ($field['invalid'])<x-icon name="warning" class="mt-1 size-4 shrink-0" /><span>{{ $field['error'] }}</span>@endif</p>
</div>
