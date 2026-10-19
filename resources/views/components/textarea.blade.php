{{--
    Multi-line field in the form style. With `maxlength`, a live "n / max characters"
    counter is shown under the field (Alpine), announced politely to screen readers.
--}}
@props(['name', 'label', 'value' => null, 'required' => false, 'maxlength' => null, 'rows' => 6, 'hint' => null])
@php
    $current = (string) old($name, $value ?? '');
    $countId = $name.'-count';
    $hintId = $name.'-hint';
    $describedBy = implode(' ', array_filter([$hint ? $hintId : null, $maxlength ? $countId : null]));
@endphp
<div @if ($maxlength) x-data="{ count: {{ mb_strlen($current) }} }" @endif>
    <label for="{{ $name }}" class="t-label block text-bronze">{{ $label }}</label>
    @if ($hint)
        <p id="{{ $hintId }}" class="mt-2 text-sm font-light text-ash">{{ $hint }}</p>
    @endif
    <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}"
              @if ($required) required @endif
              @if ($maxlength) maxlength="{{ $maxlength }}" x-on:input="count = $event.target.value.length" @endif
              @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
              {{ $attributes->merge(['class' => 'mt-2 block w-full resize-y rounded-card border border-stone bg-ivory px-4 py-3 text-body text-evergreen placeholder:text-ash focus:border-bronze']) }}>{{ $current }}</textarea>
    @if ($maxlength)
        <p id="{{ $countId }}" class="mt-2 text-sm font-light text-ash" aria-live="polite"><span x-text="count">{{ mb_strlen($current) }}</span> / {{ $maxlength }} characters</p>
    @endif
    @error($name)
        <p class="mt-2 text-sm text-bronze" role="alert">{{ $message }}</p>
    @enderror
</div>
