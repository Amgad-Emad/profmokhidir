@props(['name', 'label', 'type' => 'text', 'value' => null, 'required' => false, 'autocomplete' => null])
<div>
    <label for="{{ $name }}" class="label block text-bronze">{{ $label }}</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
           value="{{ $type === 'password' ? '' : old($name, $value) }}"
           @if($required) required @endif
           @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
           {{ $attributes->merge(['class' => 'mt-2 block w-full rounded-card border border-stone bg-ivory px-4 py-3 text-body text-evergreen placeholder:text-ash focus:border-bronze']) }}>
    @error($name)
        <p class="mt-2 text-sm text-bronze" role="alert">{{ $message }}</p>
    @enderror
</div>
