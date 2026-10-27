{{--
    A labelled choice of one: a fieldset whose legend is the label, the hint under it, then
    the options (value => label, or value => [label, hint]). `variant="segmented"` sets them
    as a pill group (draft or published); `inline` puts plain options on one line. Arrow keys
    move between options as browsers do for radios. Plain forms pass `name`, Livewire `model`
    (bound live, so dependent fields can react).
--}}
@props([
    'name' => null,
    'model' => null,
    'label',
    'options' => [],
    'value' => null,
    'hint' => null,
    'required' => false,
    'optional' => null,
    'id' => null,
    'bag' => 'default',
    'error' => null,
    'variant' => 'list',
    'inline' => false,
    'icons' => [],
])
@inject('forms', 'App\Services\FormFieldService')
@php
    $field = $forms->describe(['error' => $error, 
        'name' => $name, 'model' => $model, 'wire' => 'live',
        'id' => $id, 'label' => $label, 'hint' => $hint, 'required' => $required,
        'optional' => $optional, 'bag' => $bag,
    ], $errors ?? null);
    $groupName = $name ?? $field['id'];
    $selected = $model === null ? (string) old($field['key'], $value ?? '') : null;
    $segmented = $variant === 'segmented';
@endphp
<x-form.field as="fieldset" :field="$field" :class="$attributes->get('class')" role="radiogroup" :aria-required="$required ? 'true' : null" :aria-invalid="$field['invalid'] ? 'true' : null">
    <div @class(['form-segmented' => $segmented, 'form-choices' => ! $segmented, 'form-choices--inline' => $inline && ! $segmented]) {{ $attributes->except(['class']) }}>
        @foreach ($options as $optionValue => $option)
            @php
                [$optionLabel, $optionHint] = is_array($option) ? [$option[0], $option[1] ?? null] : [$option, null];
                $optionId = $field['id'].'-'.(\Illuminate\Support\Str::slug((string) $optionValue) ?: 'any');
                $radio = new \Illuminate\View\ComponentAttributeBag(array_filter([
                    'type' => 'radio',
                    'id' => $optionId,
                    'name' => e($groupName),
                    'value' => e((string) $optionValue),
                    'wire:model.live' => $model,
                    'checked' => $selected !== null && $selected === (string) $optionValue,
                    'required' => $required && $model === null && $loop->first,
                    'data-msg-missing' => $loop->first && $required && $model === null ? e(__(\App\Constants\FormControls::MESSAGE_CHOOSE, ['label' => \Illuminate\Support\Str::lower($label)])) : null,
                    'aria-invalid' => $field['invalid'] ? 'true' : null,
                ], fn ($v, $k) => $k === 'value' || ($v !== null && $v !== false && $v !== ''), ARRAY_FILTER_USE_BOTH));
            @endphp
            @if ($segmented)
                <label class="form-segmented__option" for="{{ $optionId }}">
                    <input class="form-segmented__input" {{ $radio }}>
                    @isset($icons[$optionValue])<x-icon :name="$icons[$optionValue]" class="size-4" />@endisset
                    <span>{{ $optionLabel }}</span>
                </label>
            @else
                <label class="form-check" for="{{ $optionId }}">
                    <input class="form-check__box" {{ $radio }}>
                    <span class="form-check__text">{{ $optionLabel }}@if ($optionHint)<span class="form-check__hint">{{ $optionHint }}</span>@endif</span>
                </label>
            @endif
        @endforeach
    </div>
    {{ $slot }}
</x-form.field>
