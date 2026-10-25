{{--
    A multi-line field that grows with its text (CSS field-sizing where the browser has it,
    forms.js elsewhere) from `rows` up to 40rem, then scrolls. With `maxlength`, a counter
    "n / max characters" sits under it and is announced only near the limit. `mono` sets it
    in JetBrains Mono (embed codes and other technical text only). Same `name`/`model`
    conventions as <x-form.input>.
--}}
@props([
    'name' => null,
    'model' => null,
    'live' => false,
    'label',
    'value' => null,
    'hint' => null,
    'required' => false,
    'optional' => null,
    'id' => null,
    'bag' => 'default',
    'error' => null,
    'maxlength' => null,
    'counter' => true,
    'rows' => 4,
    'hideLabel' => false,
    'mono' => false,
    'autogrow' => true,
    'control' => [],
    'controlClass' => null,
])
@inject('forms', 'App\Services\FormFieldService')
@php
    $field = $forms->describe(['error' => $error, 
        'name' => $name, 'model' => $model, 'wire' => $live ? 'live.debounce.400ms' : 'live.blur',
        'id' => $id, 'label' => $label, 'hint' => $hint, 'required' => $required,
        'optional' => $optional, 'bag' => $bag, 'maxlength' => $maxlength, 'counter' => $counter,
    ], $errors ?? null);
    $current = $model === null ? (string) old($field['key'], $value ?? '') : '';
    $textareaAttributes = $forms->controlAttributes($field, array_merge(
        ['rows' => $rows, 'placeholder' => \App\Constants\FormControls::BLANK_PLACEHOLDER, 'data-autogrow' => $autogrow ? 'true' : null],
        $forms->browserMessages($field, 'textarea', array_filter(['minlength' => $attributes->get('minlength')])),
        $control,
    ), $attributes->except(['class'])->getAttributes());
@endphp
<x-form.field :field="$field" :hide-label="$hideLabel" :class="$attributes->get('class')" :counter-text="$field['counter'] ? strtr(\App\Constants\FormControls::COUNTER_FORMAT, [':count' => (string) mb_strlen($model === null ? $current : (string) $value), ':max' => (string) $maxlength]) : null">
    <div class="form-control">
        <textarea @class(['form-control__input', 'form-control__input--mono' => $mono, $controlClass => $controlClass !== null]) {{ $textareaAttributes }}>{{ $current }}</textarea>
        {{ $action ?? '' }}
    </div>
</x-form.field>
