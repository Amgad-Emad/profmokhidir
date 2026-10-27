{{--
    A searchable choice of one for long lists (albums, courses): type to narrow the list, arrow
    keys to move, Enter to choose, Escape to close (the ARIA 1.2 combobox with a listbox popup).
    `options` is value => label. Livewire binds `model` live through x-modelable; plain forms
    get a hidden input named `name`. Without JavaScript a native select takes its place.
    Alpine component `formCombobox` (forms.js).
--}}
@props([
    'name' => null,
    'model' => null,
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
])
@use('App\Constants\FormControls')
@inject('forms', 'App\Services\FormFieldService')
@php
    $field = $forms->describe(['error' => $error, 
        'name' => $name, 'model' => $model, 'wire' => 'live', 'id' => $id, 'label' => $label,
        'hint' => $hint, 'required' => $required, 'optional' => $optional, 'bag' => $bag,
    ], $errors ?? null);
    $listId = $field['id'].'-listbox';
    $current = $model === null ? (string) old($field['key'], $value ?? '') : null;
    $searchAttributes = $forms->controlAttributes(array_merge($field, ['model' => null, 'name' => null, 'required' => false]), array_merge([
        'type' => 'text',
        'role' => 'combobox',
        'autocomplete' => 'off',
        'spellcheck' => 'false',
        'aria-autocomplete' => 'list',
        'aria-controls' => $listId,
        'aria-expanded' => 'false',
        'aria-required' => $required ? 'true' : null,
        'placeholder' => $placeholder ?? \App\Constants\FormControls::BLANK_PLACEHOLDER,
    ], $forms->browserMessages(array_merge($field, ['model' => null]), 'combobox')));
    $choices = collect($options)->map(fn ($optionLabel, $optionValue) => ['value' => (string) $optionValue, 'label' => (string) $optionLabel])->values()->all();
@endphp
<x-form.field :field="$field" :class="$attributes->get('class')">
    <div class="form-control form-control--select" data-combobox
         x-data="formCombobox({{ \Illuminate\Support\Js::from(['options' => $choices, 'value' => $current, 'noMatches' => __(FormControls::COMBOBOX_NO_MATCHES), 'results' => __(FormControls::COMBOBOX_RESULTS)]) }})"
         @if ($model) x-modelable="value" wire:model.live="{{ $model }}" @endif
         x-on:click.outside="close(false)" x-bind:data-filled="value ? 'true' : 'false'">
        <input class="form-control__input" {{ $searchAttributes }} x-ref="search" x-model="query"
               x-bind:aria-expanded="open ? 'true' : 'false'" x-bind:aria-activedescendant="activeId"
               x-on:input="filter()" x-on:focus="$el.select()" x-on:click="show()" x-on:blur="commit()"
               x-on:keydown.down.prevent="move(1)" x-on:keydown.up.prevent="move(-1)" x-on:keydown.enter="enter($event)"
               x-on:keydown.escape="close(true)" x-on:keydown.tab="close(false)">
        @if ($model === null && $name !== null)
            <input type="hidden" name="{{ $name }}" x-bind:value="value" value="{{ $current }}" @if ($required) data-required @endif>
        @endif
        <ul id="{{ $listId }}" class="form-listbox" role="listbox" aria-labelledby="{{ $field['id'] }}-label" x-show="open" x-cloak>
            <template x-for="(option, index) in results" x-bind:key="option.value">
                <li class="form-listbox__option" role="option" x-bind:id="'{{ $listId }}-' + index" x-bind:aria-selected="option.value === value ? 'true' : 'false'"
                    x-bind:data-active="index === active ? 'true' : 'false'" x-on:mousedown.prevent="choose(option)" x-on:mousemove="active = index">
                    <span x-text="option.label"></span>
                    <x-icon name="check" class="size-4" x-show="option.value === value" />
                </li>
            </template>
            <li class="form-listbox__empty" role="presentation" x-show="results.length === 0">{{ __(FormControls::COMBOBOX_NO_MATCHES) }}</li>
        </ul>
        <p class="sr-only" aria-live="polite" x-text="status"></p>
    </div>
    <noscript>
        <div class="form-control form-control--select mt-3">
            <select class="form-control__input" @if ($name) name="{{ $name }}" @endif aria-labelledby="{{ $field['id'] }}-label">
                <option value="">{{ $placeholder ?? __(FormControls::SELECT_PLACEHOLDER) }}</option>
                @foreach ($options as $optionValue => $optionLabel)
                    <option value="{{ $optionValue }}" @selected($current === (string) $optionValue)>{{ $optionLabel }}</option>
                @endforeach
            </select>
        </div>
    </noscript>
</x-form.field>
