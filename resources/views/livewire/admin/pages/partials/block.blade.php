{{--
    One page block in the editor: the field its type needs, labelled in plain words, with the
    character limit and the placeholders it understands. Required blocks show the original as their
    placeholder: emptied, they keep it.
    Blocks with words have English | العربية tabs: the Arabic tab (partials/block-arabic) holds the
    Arabic words only, right to left; left empty, Arabic pages show the original wording in Arabic.
--}}
@use('App\Constants\PageBlockType')
@use('App\Support\PageBlockDefinition')
@php
    $model = 'form.'.$key;
    $value = $form[$key] ?? null;
    $tokens = $block->tokens === [] ? null : __('Placeholders: :list.', ['list' => collect($block->tokens)->map(fn ($what, $token) => $token.' ('.__($what).')')->implode(', ')]);
    $guard = $block->required && in_array($block->type, [PageBlockType::ShortText, PageBlockType::LongText], true) ? __('Left empty, the original wording is kept.') : null;
    $hint = trim(implode(' ', array_filter([$block->hint === null ? null : __($block->hint), $tokens, $guard])));
    $placeholder = $block->required && is_string($block->default) && $block->type === PageBlockType::ShortText ? $block->default : null;
    $media = $images[$key] ?? null;
    $bilingual = match ($block->type) {
        PageBlockType::Image => $media !== null,
        PageBlockType::Seo => $block->fields !== [] || $media !== null,
        default => true,
    };
    $arabicValue = $arabic[$key] ?? null;
    $tabsId = 'block-'.$key.'-lang';
    // Open on the dashboard's language: an Arabic dashboard shows the Arabic wording first.
    $startTab = app()->getLocale() === \App\Constants\Locales::AR ? \App\Constants\Locales::AR : \App\Constants\Locales::EN;
@endphp
<div class="page-block form-grid" data-page-block="{{ $key }}" data-block-type="{{ $block->type->value }}" wire:key="block-{{ $key }}">
    @if ($bilingual)
        <div class="locale-tabs grid gap-6" x-data="{ tab: @js($startTab) }" data-locale-tabs>
            <div class="flex flex-wrap items-center gap-2 border-b border-rule" role="tablist" aria-label="{{ __('Language of :block', ['block' => __($block->label)]) }}">
                <button type="button" role="tab" id="{{ $tabsId }}-en" aria-controls="{{ $tabsId }}-en-panel" class="admin-tab inline-flex items-center gap-2" x-ref="en" x-bind:aria-selected="(tab === 'en').toString()" x-bind:tabindex="tab === 'en' ? 0 : -1" x-on:click="tab = 'en'" x-on:keydown.right.prevent="tab = 'ar'; $refs.ar.focus()" x-on:keydown.left.prevent="tab = 'ar'; $refs.ar.focus()" lang="en">English</button>
                <button type="button" role="tab" id="{{ $tabsId }}-ar" aria-controls="{{ $tabsId }}-ar-panel" class="admin-tab inline-flex items-center gap-2" x-ref="ar" x-bind:aria-selected="(tab === 'ar').toString()" x-bind:tabindex="tab === 'ar' ? 0 : -1" x-on:click="tab = 'ar'" x-on:keydown.right.prevent="tab = 'en'; $refs.en.focus()" x-on:keydown.left.prevent="tab = 'en'; $refs.en.focus()" lang="ar" data-arabic-tab>
                    العربية
                </button>
            </div>
            <div role="tabpanel" id="{{ $tabsId }}-ar-panel" aria-labelledby="{{ $tabsId }}-ar" class="locale-tabs__panel form-grid" data-content-lang="ar" x-show="tab === 'ar'" @if ($startTab !== 'ar') style="display: none" @endif tabindex="0" data-arabic-panel>
                @include('livewire.admin.pages.partials.block-arabic', ['key' => $key, 'block' => $block, 'value' => $arabicValue, 'english' => $value, 'media' => $media, 'sharedHint' => $hint])
            </div>
            <div role="tabpanel" id="{{ $tabsId }}-en-panel" aria-labelledby="{{ $tabsId }}-en" class="locale-tabs__panel form-grid" data-content-lang="en" x-show="tab === 'en'" @if ($startTab !== 'en') style="display: none" @endif tabindex="0">
    @endif
    @switch($block->type)
        @case(PageBlockType::ShortText)
            @if ($block->max >= \App\Support\PageBlockDefinition::LONG_FIELD)
                <x-form.textarea :model="$model" :value="$value" :label="__($block->label)" :hint="$hint ?: null" :maxlength="$block->max" rows="2" :optional="$block->required ? false : null" :placeholder="$placeholder" />
            @else
                <x-form.input :model="$model" :value="$value" :label="__($block->label)" :hint="$hint ?: null" :maxlength="$block->max" :counter="true" :optional="$block->required ? false : null" :placeholder="$placeholder" />
            @endif
            @break

        @case(PageBlockType::LongText)
            <x-form.editor :model="$model" :label="__($block->label)" :hint="trim(($hint ? $hint.' ' : '').($block->inline ? __('Up to :max characters; bold, italic and links only.', ['max' => $block->max]) : __('Up to :max characters.', ['max' => $block->max])))" :optional="$block->required ? false : null" />
            @break

        @case(PageBlockType::Link)
            <fieldset class="form-fieldset">
                <legend class="t-label form-label">{{ __($block->label) }}</legend>
                @if ($block->hint)<p class="form-hint">{{ __($block->hint) }}</p>@endif
                <div class="mt-4 form-grid">
                    <div class="form-row">
                        <x-form.input class="form-col--half" :model="$model.'.label'" :value="$value['label'] ?? ''" :label="__('Label')" :maxlength="$block->max" :counter="true" :optional="$block->required ? false : null" :placeholder="\App\Constants\Pages::showsOnlyEntered($pageKey ?? '') ? null : $block->default['label']" />
                        <x-form.select class="form-col--half" :model="$model.'.route'" :label="__('Goes to')" :options="$linkOptions" :optional="false" />
                    </div>
                    <x-form.input :model="$model.'.url'" :label="__('Or a web address')" type="url" :hint="__('Only for a page outside this site, beginning with https://. It replaces the page chosen above.')" />
                </div>
            </fieldset>
            @break

        @case(PageBlockType::List)
            @php $defaults = array_column((array) $block->default, null, 'key'); $count = count($value ?? []); @endphp
            <fieldset class="form-fieldset" data-page-list="{{ $key }}">
                <legend class="t-label form-label">{{ __($block->label) }}</legend>
                @if ($block->hint)<p class="form-hint">{{ __($block->hint) }}</p>@endif
                <ol class="page-list mt-4">
                    @foreach ($value ?? [] as $index => $item)
                        @php
                            $name = isset($item['key'], $defaults[$item['key']]) ? __($defaults[$item['key']]['label'] ?? $item['key']) : __($block->itemLabel).' '.($index + 1);
                            // The heading also names the item by its first words (a chapter's years), so items are told apart at a glance.
                            $firstField = array_key_first(array_filter($block->editableFields(), fn ($spec) => in_array($spec['kind'], [PageBlockDefinition::FIELD_TEXT, PageBlockDefinition::FIELD_PLAIN], true)));
                            $heading = trim($name.(filled($firstField ? ($item[$firstField] ?? null) : null) ? ' · '.$item[$firstField] : '')).(! empty($item[PageBlockDefinition::HIDDEN]) ? ' · '.__('Hidden') : '');
                        @endphp
                        @php $canRemove = ! $block->fixedItems && $count > ((array) $block->default === [] ? 0 : 1); @endphp
                        <li class="page-list__item page-list__item--collapsed" wire:key="{{ $key }}-{{ $item['key'] ?? $index }}" x-data="collapsible('item-{{ $key }}-{{ $index }}', false, false, 'list-{{ $key }}')" x-on:focusin="focusIn($event)" x-on:click="toggleFromClick($event)" x-bind:class="{ 'page-list__item--collapsed': ! open }">
                            @include('livewire.admin.pages.partials.list-item-head', ['name' => $name, 'bodyId' => 'item-'.$key.'-'.$index.'-body', 'idPrefix' => 'head', 'canRemove' => $canRemove])
                            <div id="item-{{ $key }}-{{ $index }}-body" class="fold" x-bind:class="{ 'fold--open': open, 'fold--settled': settled }" x-bind:inert="! open" inert data-collapsible-body><div class="fold__inner page-list__body">
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
                                        @endphp
                                        @if ($spec['kind'] === PageBlockDefinition::FIELD_ROUTE)
                                            <x-form.select @class(['form-col--half' => $half]) :model="$model.'.'.$index.'.'.$field" :label="$fieldLabel" :options="$linkOptions" :optional="false" />
                                        @elseif ($spec['kind'] === PageBlockDefinition::FIELD_IMAGE)
                                            @php $itemMedia = $images[$key.'.'.$index] ?? null; @endphp
                                            <div class="w-full">
                                                <x-form.upload :model="'uploads.'.$key.'.'.$index" :label="$fieldLabel" :hint="$imageHint" :media="$itemMedia" :href="$itemMedia?->getUrl()" :remove="$itemMedia ? 'removeListImage(\''.$key.'\', '.$index.')' : null" accept="image/jpeg,image/png,image/webp" :image="true" />
                                            </div>
                                        @elseif ($spec['kind'] === PageBlockDefinition::FIELD_TOGGLE)
                                            <x-form.toggle :model="$model.'.'.$index.'.'.$field" :label="$fieldLabel" :hint="$field === PageBlockDefinition::HIDDEN ? __('Hidden items stay here and leave the page until you switch this off.') : null" />
                                        @elseif (PageBlockDefinition::isLong($spec))
                                            <x-form.textarea :model="$model.'.'.$index.'.'.$field" :value="$item[$field] ?? ''" :label="$fieldLabel" :maxlength="$spec['max']" :counter="true" rows="2" :optional="$required ? false : null" />
                                        @else
                                            <x-form.input @class(['form-col--half' => $half]) :model="$model.'.'.$index.'.'.$field" :value="$item[$field] ?? ''" :label="$fieldLabel" :maxlength="$spec['max']" :counter="true" :optional="$required ? false : null" />
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
                                    @if ($block->sortable)
                                        <x-button variant="quiet" size="sm" type="button" wire:click="moveItem('{{ $key }}', {{ $index }}, -1)" :disabled="$index === 0">{{ __('Move up') }}<span class="sr-only"> {{ $name }}</span></x-button>
                                        <x-button variant="quiet" size="sm" type="button" wire:click="moveItem('{{ $key }}', {{ $index }}, 1)" :disabled="$index === $count - 1">{{ __('Move down') }}<span class="sr-only"> {{ $name }}</span></x-button>
                                    @endif
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
            @php $isSeo = $block->type === PageBlockType::Seo; @endphp
            @if ($isSeo && $block->fields !== [])
                <x-form.input :model="$model.'.title'" :value="$value['title'] ?? ''" :label="__('Search title')" :maxlength="PageBlockDefinition::SEO_TITLE_MAX" :counter="true" :hint="__('Shown as the link in search results and on the browser tab, before the site name.')" />
                <x-form.textarea :model="$model.'.description'" :value="$value['description'] ?? ''" :label="__('Search description')" :maxlength="PageBlockDefinition::SEO_DESCRIPTION_MAX" rows="3" :hint="__('One or two sentences under the link in search results and when the page is shared.')" />
            @endif
            @if ($isSeo && $block->hint)<p class="form-hint">{{ __($block->hint) }}</p>@endif
            @include('livewire.admin.pages.partials.image-fields', ['wordsModel' => $model, 'suffix' => '', 'words' => ['altHint' => __(\App\Constants\FormControls::UPLOAD_ALT_PROMPT)]])
            @break
    @endswitch
    @if ($bilingual)
            </div>
        </div>
    @endif

</div>
