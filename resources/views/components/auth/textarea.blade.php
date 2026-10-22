{{--
    A multi-line field in the underlined style (the community application's reason). With
    `maxlength`, a live "n / max characters" count sits under the field, announced politely.
--}}
@props(['name', 'label', 'value' => null, 'required' => false, 'maxlength' => null, 'rows' => 5, 'hint' => null, 'id' => null, 'bag' => 'default'])
@php
    $id ??= $name;
    $current = (string) old($name, $value ?? '');
    $countId = $id.'-count';
    $hintId = $id.'-hint';
    $errorId = $id.'-error';
    $hasError = $errors->getBag($bag)->has($name);
    $describedBy = implode(' ', array_filter([$hint ? $hintId : null, $maxlength ? $countId : null, $hasError ? $errorId : null]));
@endphp
<div {{ $attributes->class(['field', 'field--invalid' => $hasError]) }} @if ($maxlength) x-data="{ count: {{ mb_strlen($current) }} }" @endif>
    <label for="{{ $id }}" class="t-label block text-bronze">{{ $label }}</label>
    @if ($hint)
        <p id="{{ $hintId }}" class="field__hint">{{ $hint }}</p>
    @endif
    <div class="field__control">
        @php
            $fieldBag = new \Illuminate\View\ComponentAttributeBag(array_filter([
                'required' => $required,
                'maxlength' => $maxlength,
                'x-on:input' => $maxlength ? 'count = $event.target.value.length' : null,
                'aria-describedby' => $describedBy !== '' ? $describedBy : null,
                'aria-invalid' => $hasError ? 'true' : null,
            ], fn ($value): bool => $value !== null && $value !== false && $value !== ''));
        @endphp
        <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" class="field__input resize-y"{!! $fieldBag->isNotEmpty() ? ' '.$fieldBag : '' !!}>{{ $current }}</textarea>
        <span class="field__line" aria-hidden="true"></span>
    </div>
    @if ($maxlength)
        <p id="{{ $countId }}" class="field__note" aria-live="polite"><span x-text="count">{{ mb_strlen($current) }}</span>&nbsp;/ {{ $maxlength }} characters</p>
    @endif
    @error($name, $bag)
        <p id="{{ $errorId }}" class="field__error" role="alert"><x-icon name="warning" class="mt-0.5 size-4 shrink-0" /><span>{{ $message }}</span></p>
    @enderror
</div>
