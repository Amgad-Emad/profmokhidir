{{--
    A styled native select: the browser's own list (best on phones and with screen readers)
    inside the shared frame, a hairline chevron drawn in the current ink. `options` is
    value => label, or group label => [value => label] for <optgroup>s. For long lists that
    people need to search, use <x-form.combobox>. Livewire selects bind with `.live` when
    `live` is set (filters), otherwise on blur.
--}}
@props([
    'name' => null,
    'model' => null,
    'live' => false,
    'label',
    'options' => [],
    'value' => null,
    'placeholder' => null,
    'hint' => null,
    'required' => false,
    'optional' => null,
    'id' => null,
    'bag' => 'default',
    'error' => null,
    'hideLabel' => false,
    'size' => null,
    'loading' => null,
])
@inject('forms', 'App\Services\FormFieldService')
@php
    $field = $forms->describe(['error' => $error, 
        'name' => $name, 'model' => $model, 'wire' => $live ? 'live' : 'live.change',
        'id' => $id, 'label' => $label, 'hint' => $hint, 'required' => $required,
        'optional' => $optional, 'bag' => $bag,
    ], $errors ?? null);
    $selected = $model === null ? (string) old($field['key'], $value ?? '') : null;
    $selectAttributes = $forms->controlAttributes($field, array_merge(
        $forms->browserMessages($field, 'select'),
    ), $attributes->except(['class'])->getAttributes());
    $isSelected = fn ($optionValue): bool => $selected !== null && $selected === (string) $optionValue;
@endphp
<x-form.field :field="$field" :hide-label="$hideLabel" :class="$attributes->get('class')">
    <div @class(['form-control', 'form-control--select', 'form-control--compact' => $size === 'compact']) @if ($loading) wire:loading.attr="data-loading" wire:target="{{ $loading }}" @endif>
        <select class="form-control__input" {{ $selectAttributes }}>
            @if ($placeholder !== null)
                <option value=""{!! $isSelected('') ? ' selected' : '' !!}>{{ $placeholder }}</option>
            @endif
            @foreach ($options as $optionValue => $optionLabel)
                @if (is_array($optionLabel))
                    <optgroup label="{{ $optionValue }}">
                        @foreach ($optionLabel as $groupValue => $groupLabel)
                            <option value="{{ $groupValue }}"{!! $isSelected($groupValue) ? ' selected' : '' !!}>{{ $groupLabel }}</option>
                        @endforeach
                    </optgroup>
                @else
                    <option value="{{ $optionValue }}"{!! $isSelected($optionValue) ? ' selected' : '' !!}>{{ $optionLabel }}</option>
                @endif
            @endforeach
        </select>
    </div>
</x-form.field>
