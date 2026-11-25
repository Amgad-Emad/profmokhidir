{{--
    The Arabic tab of a page block: the same fields, labels, hints, groups and controls as the English
    tab (partials/block), in the same order, so the two tabs read alike. Words are this tab's own
    (right to left); everything that is one value for both languages (years, numerals, switches,
    images, destinations, item order) is bound to the English form, so it can be changed from either tab.
    On most pages an empty Arabic field shows the original wording in Arabic (the placeholder shows it);
    on a page that shows only what was entered (Pages::ENTERED_ONLY) empty is empty, with no placeholder.
--}}
@use('App\Constants\PageBlockType')
@use('App\Support\PageBlockDefinition')
@php
    $model = 'arabic.'.$key;
    $englishBlock = 'form.'.$key;
    $enteredOnly = \App\Constants\Pages::showsOnlyEntered($pageKey ?? '');
    $shown = $block->localized()->translate($english);
    // The English tab's hint, word for word; elsewhere than an entered-only page, plus what an empty field does.
    $fallback = $enteredOnly ? null : __('Left empty, Arabic pages show the wording in the placeholder.');
    $hint = trim(implode(' ', array_filter([$sharedHint ?? null, $fallback]))) ?: null;
    $optional = $block->required ? false : null;
@endphp
@switch($block->type)
    @case(PageBlockType::ShortText)
        @if ($block->max >= \App\Support\PageBlockDefinition::LONG_FIELD)
            <x-form.textarea :model="$model" :value="$value" :label="__($block->label)" :hint="$hint" :maxlength="$block->max" rows="2" :optional="null" :placeholder="! $enteredOnly && is_string($shown) ? $shown : null" dir="rtl" />
        @else
            <x-form.input :model="$model" :value="$value" :label="__($block->label)" :hint="$hint" :maxlength="$block->max" :counter="true" :optional="null" :placeholder="! $enteredOnly && is_string($shown) ? $shown : null" dir="rtl" />
        @endif
        @break

    @case(PageBlockType::LongText)
        <x-form.editor :model="$model" :label="__($block->label)" :hint="trim(($hint ? $hint.' ' : '').($block->inline ? __('Up to :max characters; bold, italic and links only.', ['max' => $block->max]) : __('Up to :max characters.', ['max' => $block->max])))" :optional="null" />
        @break

    @case(PageBlockType::Link)
        <fieldset class="form-fieldset">
            <legend class="t-label form-label">{{ __($block->label) }}</legend>
            @if ($block->hint)<p class="form-hint">{{ __($block->hint) }}</p>@endif
            <div class="mt-4 form-grid">
                <div class="form-row">
                    <x-form.input class="form-col--half" :model="$model.'.label'" :value="$value['label'] ?? ''" :label="__('Label')" :hint="$fallback" :maxlength="$block->max" :counter="true" :optional="null" :placeholder="$enteredOnly ? null : ($shown['label'] ?? null)" dir="rtl" />
                    <x-form.select class="form-col--half" :model="$englishBlock.'.route'" :id="'ar-'.str_replace('.', '-', $englishBlock).'-route'" :label="__('Goes to')" :options="$linkOptions" :optional="false" />
                </div>
                <x-form.input :model="$englishBlock.'.url'" :id="'ar-'.str_replace('.', '-', $englishBlock).'-url'" :label="__('Or a web address')" type="url" :hint="__('Only for a page outside this site, beginning with https://. It replaces the page chosen above.')" dir="ltr" />
            </div>
        </fieldset>
        @break

    @case(PageBlockType::List)
        @php $defaults = array_column((array) $block->default, null, 'key'); $count = count((array) $english); @endphp
        <fieldset class="form-fieldset" data-page-list-arabic="{{ $key }}">
            <legend class="t-label form-label">{{ __($block->label) }}</legend>
            @if ($block->hint)<p class="form-hint">{{ __($block->hint) }}</p>@endif
            <ol class="page-list mt-4">
                @foreach ((array) $english as $index => $item)
                    @php
                        $name = isset($item['key'], $defaults[$item['key']]) ? __($defaults[$item['key']]['label'] ?? $item['key']) : __($block->itemLabel).' '.($index + 1);
                        $englishModel = $englishBlock.'.'.$index.'.';
                        $canRemove = ! $block->fixedItems && $count > ((array) $block->default === [] ? 0 : 1);
                    @endphp
                    <li class="page-list__item page-list__item--collapsed" wire:key="{{ $key }}-ar-{{ $item['key'] ?? $index }}" x-data="collapsible('item-ar-{{ $key }}-{{ $index }}', false, false, 'list-ar-{{ $key }}')" x-on:focusin="focusIn($event)" x-on:click="toggleFromClick($event)" x-bind:class="{ 'page-list__item--collapsed': ! open }">
                        @include('livewire.admin.pages.partials.list-item-head', ['name' => $name, 'bodyId' => 'item-ar-'.$key.'-'.$index.'-body', 'idPrefix' => 'head-ar', 'canRemove' => $canRemove])
                        <div id="item-ar-{{ $key }}-{{ $index }}-body" class="fold" x-bind:class="{ 'fold--open': open, 'fold--settled': settled }" x-bind:inert="! open" inert data-collapsible-body><div class="fold__inner page-list__body">
                        @php
                            $itemGroups = PageBlockDefinition::grouped(array_filter($block->editableFields(), fn ($spec) => ! PageBlockDefinition::isInline($spec)));
                            // Extra milestones stay out of sight while empty; "Add milestone" shows the next one.
                            $emptyGroups = array_values(array_diff(array_keys(PageBlockDefinition::emptyAddableGroups($itemGroups, (array) $item)), $addedGroups[$key][$index] ?? []));
                            $addThing = collect(PageBlockDefinition::emptyAddableGroups($itemGroups, (array) $item))->first();
                                $addLabel = 'Add '.$addThing; // e.g. "Add milestone" (translated in lang/ar.json)
                            $lastAddable = collect($itemGroups)->filter(fn ($fields) => collect($fields)->pluck('addable')->filter()->isNotEmpty())->keys()->last();
                        @endphp
                        <div class="page-list__groups">
                        @foreach ($itemGroups as $group => $fields)
                            @php
                                $fields = PageBlockDefinition::laidOut($fields);
                                $pairShort = count(array_filter($fields, fn ($spec) => PageBlockDefinition::isShort($spec))) > 1;
                            @endphp
                            @php $emptyAt = in_array($group, $emptyGroups, true); @endphp
                            @unless ($emptyAt)
                            @if ($group !== '')<fieldset class="page-list__group"><legend class="page-list__legend">{{ __($group) }}</legend>@endif
                            <div class="form-row">
                                @foreach ($fields as $field => $spec)
                                    @php
                                        $required = PageBlockDefinition::isRequired($spec);
                                        $fieldLabel = __($spec['label']).($group === '' ? ' ('.$name.')' : '');
                                        $half = $pairShort && PageBlockDefinition::isShort($spec);
                                        $sharedId = 'ar-'.str_replace('.', '-', $englishModel.$field);
                                    @endphp
                                    @if ($spec['kind'] === PageBlockDefinition::FIELD_ROUTE)
                                        <x-form.select @class(['form-col--half' => $half]) :model="$englishModel.$field" :id="$sharedId" :label="$fieldLabel" :options="$linkOptions" :optional="false" />
                                    @elseif ($spec['kind'] === PageBlockDefinition::FIELD_IMAGE)
                                        @php $itemMedia = $images[$key.'.'.$index] ?? null; @endphp
                                        <div class="w-full">
                                            <x-form.upload :model="'uploads.'.$key.'.'.$index" :id="'uploads-'.$key.'-'.$index.'-ar'" :label="$fieldLabel" :hint="$imageHint" :media="$itemMedia" :href="$itemMedia?->getUrl()" :remove="$itemMedia ? 'removeListImage(\''.$key.'\', '.$index.')' : null" accept="image/jpeg,image/png,image/webp" :image="true" />
                                        </div>
                                    @elseif ($spec['kind'] === PageBlockDefinition::FIELD_TOGGLE)
                                        <x-form.toggle :model="$englishModel.$field" :id="$sharedId" :label="$fieldLabel" :hint="$field === PageBlockDefinition::HIDDEN ? __('Hidden items stay here and leave the page until you switch this off.') : null" />
                                    @elseif ($spec['kind'] === PageBlockDefinition::FIELD_PLAIN)
                                        {{-- Years and figures: one value for both languages. --}}
                                        <x-form.input @class(['form-col--half' => $half]) :model="$englishModel.$field" :id="$sharedId" :value="$item[$field] ?? ''" :label="$fieldLabel" :maxlength="$spec['max']" :counter="true" :optional="$required ? false : null" dir="ltr" />
                                    @elseif (PageBlockDefinition::isLong($spec))
                                        <x-form.textarea :model="$model.'.'.$index.'.'.$field" :value="$value[$index][$field] ?? ''" :label="$fieldLabel" :maxlength="$spec['max']" :counter="true" rows="2" :optional="$required ? false : null" :placeholder="$enteredOnly ? null : ($shown[$index][$field] ?? null)" dir="rtl" />
                                    @else
                                        <x-form.input @class(['form-col--half' => $half]) :model="$model.'.'.$index.'.'.$field" :value="$value[$index][$field] ?? ''" :label="$fieldLabel" :maxlength="$spec['max']" :counter="true" :optional="$required ? false : null" :placeholder="$enteredOnly ? null : ($shown[$index][$field] ?? null)" dir="rtl" />
                                    @endif
                                @endforeach
                            </div>
                            @if ($group !== '')</fieldset>@endif
                            @endunless
                            @if ($group === $lastAddable && $emptyGroups !== [])
                                <x-button variant="secondary" size="sm" type="button" class="page-list__add" wire:click="addGroup('{{ $key }}', {{ $index }}, '{{ $emptyGroups[0] }}')"><x-icon name="plus" class="size-4" />{{ __($addLabel) }}</x-button>
                            @endif
                        @endforeach
                        </div>
                        @if ($block->sortable)
                            <div class="page-list__actions">
                                <x-button variant="quiet" size="sm" type="button" wire:click="moveItem('{{ $key }}', {{ $index }}, -1)" :disabled="$index === 0">{{ __('Move up') }}<span class="sr-only"> {{ $name }}</span></x-button>
                                <x-button variant="quiet" size="sm" type="button" wire:click="moveItem('{{ $key }}', {{ $index }}, 1)" :disabled="$index === $count - 1">{{ __('Move down') }}<span class="sr-only"> {{ $name }}</span></x-button>
                            </div>
                        @endif
                        </div></div>
                    </li>
                @endforeach
            </ol>
            @if (! $block->fixedItems && $count < ($block->maxItems ?? PHP_INT_MAX))
                <x-button variant="quiet" size="sm" type="button" class="mt-4" wire:click="addItem('{{ $key }}')"><x-icon name="plus" class="size-4" />{{ __('Add :item', ['item' => \Illuminate\Support\Str::lower(__($block->itemLabel))]) }}</x-button>
            @endif
        </fieldset>
        @break

    @case(PageBlockType::Image)
    @case(PageBlockType::Seo)
        @if ($block->type === PageBlockType::Seo && $block->fields !== [])
            <x-form.input :model="$model.'.title'" :value="$value['title'] ?? ''" :label="__('Search title')" :maxlength="PageBlockDefinition::SEO_TITLE_MAX" :counter="true" :hint="__('Left empty, Arabic pages use the Arabic page heading.')" dir="rtl" />
            <x-form.textarea :model="$model.'.description'" :value="$value['description'] ?? ''" :label="__('Search description')" :maxlength="PageBlockDefinition::SEO_DESCRIPTION_MAX" rows="3" :hint="__('Left empty, Arabic pages use the Arabic introduction.')" dir="rtl" />
        @endif
        @include('livewire.admin.pages.partials.image-fields', ['key' => $key, 'wordsModel' => $model, 'suffix' => '-ar', 'isSeo' => $block->type === PageBlockType::Seo, 'value' => (array) $english, 'words' => ['altHint' => __('Describe the image in Arabic for people who cannot see it. Left empty, the English alt text is read.')]])
        @break
@endswitch
