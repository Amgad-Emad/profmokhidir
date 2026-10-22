{{--
    An underlined field for the sign-in screens and the community application: the label
    in the small-caps label style above, a 48px input on a 1px stone line that an evergreen
    line draws over on focus, the hint and the validation message beneath. Keyboard focus
    keeps the 2px bronze outline. Extra attributes for the <input> itself (Alpine bindings,
    inputmode) go in `input`; the component's own attributes land on the wrapper.
--}}
@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'required' => false,
    'autocomplete' => null,
    'hint' => null,
    'id' => null,
    'autofocus' => false,
    'inputmode' => null,
    'bag' => 'default',
    'input' => [],
])
@php
    $id ??= $name;
    $hintId = $id.'-hint';
    $errorId = $id.'-error';
    $hasError = $errors->getBag($bag)->has($name);
    $describedBy = implode(' ', array_filter([$hint ? $hintId : null, $hasError ? $errorId : null]));
    $inputBag = new \Illuminate\View\ComponentAttributeBag(array_filter([
        'required' => $required,
        'autofocus' => $autofocus,
        'autocomplete' => $autocomplete,
        'inputmode' => $inputmode,
        'aria-describedby' => $describedBy !== '' ? $describedBy : null,
        'aria-invalid' => $hasError ? 'true' : null,
    ] + $input, fn ($value): bool => $value !== null && $value !== false && $value !== ''));
@endphp
<div {{ $attributes->class(['field', 'field--invalid' => $hasError]) }}>
    <label for="{{ $id }}" class="t-label block text-accent-text">{{ $label }}</label>
    @if ($hint)
        <p id="{{ $hintId }}" class="field__hint">{{ $hint }}</p>
    @endif
    <div class="field__control">
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ $type === 'password' ? '' : old($name, $value) }}" @class(['field__input', 'field__input--toggle' => $slot->isNotEmpty()]){!! $inputBag->isNotEmpty() ? ' '.$inputBag : '' !!}>
        <span class="field__line" aria-hidden="true"></span>
        {{ $slot }}
    </div>
    @error($name, $bag)
        <p id="{{ $errorId }}" class="field__error" role="alert"><x-icon name="warning" class="mt-0.5 size-4 shrink-0" /><span>{{ $message }}</span></p>
    @enderror
</div>
