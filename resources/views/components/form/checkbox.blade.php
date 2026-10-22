{{--
    A styled checkbox: a 20px square on the strong rule that fills with the ink and an ivory
    tick. The real <input> is styled (appearance: none), so keyboard focus and the label work
    as they always do. The label text is the slot (links allowed); `hint` sits under it.
    Plain forms pass `name` (and `value`, default 1); Livewire forms pass `model`. Agreements
    pass `required` (blur message "Tick this box to continue."); a checkbox never shows
    "Optional". For an on/off setting use <x-form.toggle>.
--}}
@props([
    'name' => null,
    'model' => null,
    'live' => false,
    'value' => '1',
    'checked' => null,
    'hint' => null,
    'required' => false,
    'id' => null,
    'bag' => 'default',
    'error' => null,
])
@inject('forms', 'App\Services\FormFieldService')
@php
    $field = $forms->describe(['error' => $error, 
        'name' => $name, 'model' => $model, 'wire' => $live ? 'live' : '',
        'id' => $id, 'label' => trim(strip_tags((string) $slot)), 'hint' => null, 'required' => $required,
        'optional' => false, 'marker' => false, 'bag' => $bag,
    ], $errors ?? null);
    $hintId = $field['id'].'-note';
    $isChecked = $model === null && ($checked ?? (bool) old($field['key']));
    $boxAttributes = $forms->controlAttributes($field, array_merge(
        ['type' => 'checkbox', 'value' => $value, 'checked' => $isChecked, 'aria-describedby' => $hint ? $hintId : null, 'data-label' => null],
        $forms->browserMessages($field, 'check'),
    ), $attributes->except(['class'])->getAttributes());
@endphp
<div {{ $attributes->only('class')->class(['form-field']) }} data-form-field>
    <label class="form-check" for="{{ $field['id'] }}">
        <input class="form-check__box" {{ $boxAttributes }}>
        <span class="form-check__text">{{ $slot }}@if ($hint)<span id="{{ $hintId }}" class="form-check__hint">{{ $hint }}</span>@endif</span>
    </label>
    <p id="{{ $field['errorId'] }}" class="form-message form-error" data-form-error{!! $field['invalid'] ? ' role="alert"' : '' !!}>@if ($field['invalid'])<x-icon name="warning" class="mt-1 size-4 shrink-0" /><span>{{ $field['error'] }}</span>@endif</p>
</div>
