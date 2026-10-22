{{--
    A single-line field (text, email, url, tel, number, search). Plain forms pass `name`
    (the old() value and the error bag follow it); Livewire forms pass `model`, bound with
    wire:model.live.blur (or `live` for search boxes, debounced). `required` prints "Required" in
    the hint line, otherwise "Optional" (`:optional="false"` for filters and search boxes).
    `class` goes on the field; every other attribute lands on the <input>. A trailing button
    (clear, show) goes in the `action` slot; `loading` names a Livewire target that puts the
    control in its loading state while it runs; `frame` adds attributes to the frame itself.
--}}
@props([
    'name' => null,
    'model' => null,
    'live' => false,
    'label',
    'type' => 'text',
    'value' => null,
    'hint' => null,
    'required' => false,
    'optional' => null,
    'id' => null,
    'bag' => 'default',
    'error' => null,
    'maxlength' => null,
    'counter' => false,
    'hideLabel' => false,
    'size' => null,
    'prefix' => null,
    'mono' => false,
    'loading' => null,
    'kind' => null,
    'limits' => [],
    'control' => [],
    'controlClass' => null,
    'frame' => [],
])
@inject('forms', 'App\Services\FormFieldService')
@php
    $field = $forms->describe(['error' => $error, 
        'name' => $name, 'model' => $model, 'wire' => $live ? 'live.debounce.400ms' : 'live.blur',
        'id' => $id, 'label' => $label, 'hint' => $hint, 'required' => $required,
        'optional' => $optional, 'bag' => $bag, 'maxlength' => $maxlength, 'counter' => $counter,
    ], $errors ?? null);
    $limits = $limits + array_filter(['min' => $attributes->get('min'), 'max' => $attributes->get('max'), 'minlength' => $attributes->get('minlength')], fn ($v) => $v !== null);
    $current = $model === null && $type !== 'password' ? old($field['key'], $value) : null;
    $inputAttributes = $forms->controlAttributes($field, array_merge(
        ['type' => $type, 'value' => is_scalar($current) ? (string) $current : null, 'placeholder' => \App\Constants\FormControls::BLANK_PLACEHOLDER],
        $forms->browserMessages($field, $kind ?? $type, $limits),
        $control,
    ), $attributes->except(['class'])->getAttributes());
    $frameClasses = ['form-control', 'form-control--compact' => $size === 'compact', 'form-control--display' => $size === 'display'];
    $frameAttributes = new \Illuminate\View\ComponentAttributeBag($frame);
@endphp
<x-form.field :field="$field" :hide-label="$hideLabel" :class="$attributes->get('class')" :counter-text="$field['counter'] ? strtr(\App\Constants\FormControls::COUNTER_FORMAT, [':count' => (string) mb_strlen((string) $current), ':max' => (string) $maxlength]) : null">
    <div @class($frameClasses) {{ $frameAttributes }} @if ($loading) wire:loading.attr="data-loading" wire:target="{{ $loading }}" @endif>
        @if ($prefix)
            <span class="form-control__affix" aria-hidden="true">{{ $prefix }}</span>
        @endif
        <input @class(['form-control__input', 'form-control__input--mono' => $mono, $controlClass => $controlClass !== null]) {{ $inputAttributes }}>
        <span class="form-control__status" aria-hidden="true"><x-icon name="check" class="size-5" /></span>
        {{ $action ?? '' }}
    </div>
</x-form.field>
