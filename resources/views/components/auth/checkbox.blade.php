{{--
    A styled checkbox for plain forms: an 18px square on a 1px ash line that fills evergreen
    with an ivory tick. The real <input> is styled (appearance: none), so keyboard focus and
    the label association work as they always do. The label text is the slot.
--}}
@props(['name', 'value' => '1', 'required' => false, 'checked' => null, 'id' => null, 'bag' => 'default'])
@php
    $id ??= $name;
    $isChecked = $checked ?? (bool) old($name);
    $hasError = $errors->getBag($bag)->has($name);
    $errorId = $id.'-error';
@endphp
<div {{ $attributes->class(['check-field', 'field--invalid' => $hasError]) }}>
    <label class="check" for="{{ $id }}">
        @php
            $boxBag = new \Illuminate\View\ComponentAttributeBag(array_filter([
                'required' => $required,
                'checked' => $isChecked,
                'aria-invalid' => $hasError ? 'true' : null,
                'aria-describedby' => $hasError ? $errorId : null,
            ], fn ($value): bool => $value !== null && $value !== false));
        @endphp
        <input type="checkbox" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}" class="check__input"{!! $boxBag->isNotEmpty() ? ' '.$boxBag : '' !!}>
        <span class="check__label">{{ $slot }}</span>
    </label>
    @error($name, $bag)
        <p id="{{ $errorId }}" class="field__error" role="alert"><x-icon name="warning" class="mt-0.5 size-4 shrink-0" /><span>{{ $message }}</span></p>
    @enderror
</div>
