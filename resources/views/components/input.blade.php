@props(['name', 'label', 'type' => 'text', 'value' => null, 'required' => false, 'autocomplete' => null, 'hint' => null, 'bag' => 'default', 'id' => null])
@php
    $id ??= $name;
    $hintId = $id.'-hint';
@endphp
<div>
    <label for="{{ $id }}" class="t-label block text-bronze">{{ $label }}</label>
    @if ($hint)
        <p id="{{ $hintId }}" class="mt-2 text-sm font-light text-ash">{{ $hint }}</p>
    @endif
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}"
           value="{{ $type === 'password' ? '' : old($name, $value) }}"
           @if($required) required @endif
           @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
           @if($hint) aria-describedby="{{ $hintId }}" @endif
           {{ $attributes->merge(['class' => 'mt-2 block w-full rounded-card border border-stone bg-ivory px-4 py-3 text-body text-evergreen placeholder:text-ash focus:border-bronze']) }}>
    @error($name, $bag)
        <p class="mt-2 flex items-center gap-2 text-sm text-bronze" role="alert"><x-icon name="warning" class="size-4 shrink-0" /><span>{{ $message }}</span></p>
    @enderror
</div>
