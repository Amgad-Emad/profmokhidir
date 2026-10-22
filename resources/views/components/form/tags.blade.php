{{--
    A list of short entries (areas of expertise, topic tags) typed into one field: Enter or a
    comma adds the entry as a removable pill, Backspace in the empty field takes the last one
    back for editing, pasting "a, b, c" adds three. `suggestions` offers known entries as you
    type. Livewire binds `model` through x-modelable: an array, or with `separator` a string
    joined by it ("soil, water"). Plain forms get hidden inputs named `name[]` (or one `name`
    with `separator`). Errors of the items (form.expertise.2) show under the field.
    Alpine component `formTags` (forms.js).
--}}
@props([
    'name' => null,
    'model' => null,
    'label',
    'value' => [],
    'separator' => null,
    'suggestions' => [],
    'hint' => null,
    'required' => false,
    'optional' => null,
    'id' => null,
    'bag' => 'default',
    'error' => null,
    'max' => null,
])
@use('App\Constants\FormControls')
@inject('forms', 'App\Services\FormFieldService')
@php
    $field = $forms->describe(['error' => $error, 
        'name' => $name, 'model' => $model, 'wire' => '', 'id' => $id, 'label' => $label,
        'hint' => trim(($hint ?? '').' '.FormControls::TAGS_ADD_HINT), 'required' => $required,
        'optional' => $optional, 'bag' => $bag, 'nested' => true,
    ], $errors ?? null);
    $initial = $model === null ? old($field['key'], $value) : [];
    $initial = is_string($initial) ? array_values(array_filter(array_map('trim', explode($separator ?? ',', $initial)))) : array_values((array) $initial);
    $entryAttributes = $forms->controlAttributes(array_merge($field, ['model' => null, 'name' => null, 'required' => false, 'maxlength' => null]), [
        'type' => 'text',
        'autocomplete' => 'off',
        'enterkeyhint' => 'enter',
        'placeholder' => \App\Constants\FormControls::BLANK_PLACEHOLDER,
        'list' => $suggestions !== [] ? $field['id'].'-suggestions' : null,
        'aria-required' => $required ? 'true' : null,
    ]);
@endphp
<x-form.field :field="$field" :class="$attributes->get('class')">
    <div class="form-control form-control--tags" data-tags {{ $attributes->except(['class']) }}
         x-data="formTags({{ \Illuminate\Support\Js::from(['tags' => $initial, 'separator' => $separator, 'max' => $max, 'copy' => ['remove' => FormControls::TAGS_REMOVE, 'added' => FormControls::TAGS_ADDED, 'removed' => FormControls::TAGS_REMOVED, 'editing' => FormControls::TAGS_EDITING]]) }})"
         @if ($model) x-modelable="value" wire:model="{{ $model }}" @endif
         x-bind:data-filled="tags.length > 0 ? 'true' : 'false'" x-on:click="$refs.entry.focus()">
        <ul class="contents" role="list" aria-label="{{ $label }}">
            <template x-for="(tag, index) in tags" x-bind:key="tag + index">
                <li class="form-tag">
                    <span x-text="tag"></span>
                    <button type="button" class="form-tag__remove" x-on:click.stop="removeAt(index)" x-bind:aria-label="removeLabel(tag)"><x-icon name="close" class="size-4" /></button>
                </li>
            </template>
        </ul>
        <input class="form-control__input" {{ $entryAttributes }} x-ref="entry" x-model="draft"
               x-on:keydown.enter.prevent="add()" x-on:keydown="key($event)" x-on:blur="add()" x-on:paste="paste($event)">
        @if ($model === null && $name !== null)
            @if ($separator)
                <input type="hidden" name="{{ $name }}" x-bind:value="tags.join(@js($separator.' '))" value="{{ implode($separator.' ', $initial) }}">
            @else
                <template x-for="tag in tags"><input type="hidden" name="{{ $name }}[]" x-bind:value="tag"></template>
            @endif
        @endif
        <p class="sr-only" aria-live="polite" x-text="status"></p>
    </div>
    @if ($suggestions !== [])
        <datalist id="{{ $field['id'] }}-suggestions">
            @foreach ($suggestions as $suggestion)<option value="{{ $suggestion }}"></option>@endforeach
        </datalist>
    @endif
</x-form.field>
