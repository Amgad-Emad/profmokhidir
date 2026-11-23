{{--
    The Arabic tab of a page block: its Arabic words, laid out like the English tab. Images, crops,
    years and numerals are one value for both languages and can be changed from either tab. Every field is optional: left empty, Arabic pages show the original wording
    in Arabic, or the English words where an editor changed them and has not translated them yet.
    The placeholders show what Arabic visitors see while the field is empty.
--}}
@use('App\Constants\PageBlockType')
@use('App\Support\PageBlockDefinition')
@php
    $model = 'arabic.'.$key;
    $shown = $block->localized()->translate($english);
    $hint = __('Optional. Left empty, Arabic pages show the wording in the placeholder.');
@endphp
@switch($block->type)
    @case(PageBlockType::ShortText)
        @if ($block->max >= \App\Support\PageBlockDefinition::LONG_FIELD)
            <x-form.textarea :model="$model" :value="$value" :label="__($block->label)" :hint="$hint" :maxlength="$block->max" rows="2" :placeholder="is_string($shown) ? $shown : null" dir="rtl" />
        @else
            <x-form.input :model="$model" :value="$value" :label="__($block->label)" :hint="$hint" :maxlength="$block->max" :counter="true" :placeholder="is_string($shown) ? $shown : null" dir="rtl" />
        @endif
        @break

    @case(PageBlockType::LongText)
        <x-form.editor :model="$model" :label="__($block->label)" :hint="__('Optional. Up to :max characters. Left empty, Arabic pages show the original wording in Arabic.', ['max' => $block->max])" />
        @break

    @case(PageBlockType::Link)
        <x-form.input :model="$model.'.label'" :value="$value['label'] ?? ''" :label="__($block->label)" :hint="__('The destination is the one chosen in the English tab.')" :maxlength="$block->max" :counter="true" :placeholder="$shown['label'] ?? null" dir="rtl" />
        @break

    @case(PageBlockType::List)
        <fieldset class="form-fieldset" data-page-list-arabic="{{ $key }}">
            <legend class="t-label form-label">{{ __($block->label) }}</legend>
            <p class="form-hint">{{ __('The items, their order and destinations follow the English tab. Fill in the Arabic words; an empty field shows the placeholder.') }}</p>
            <ol class="page-list mt-4">
                @foreach ((array) $english as $index => $item)
                    @php
                        // Words are asked for in Arabic; years, numerals and switches are the same in both
                        // languages and edit the English tab's value, so they can be changed from either tab.
                        $fieldsHere = array_filter($block->editableFields(), fn ($spec) => ! PageBlockDefinition::isInline($spec) && in_array($spec['kind'], [PageBlockDefinition::FIELD_TEXT, PageBlockDefinition::FIELD_PLAIN, PageBlockDefinition::FIELD_TOGGLE], true));
                        $firstPlain = array_key_first(array_filter($block->editableFields(), fn ($spec) => $spec['kind'] === PageBlockDefinition::FIELD_PLAIN));
                        $itemName = __($block->itemLabel).' '.($index + 1).(filled($firstPlain ? ($item[$firstPlain] ?? null) : null) ? ' · '.$item[$firstPlain] : '').(! empty($item[PageBlockDefinition::HIDDEN]) ? ' · '.__('Hidden') : '');
                        $englishModel = 'form.'.$key.'.'.$index.'.';
                    @endphp
                    <li class="page-list__item page-list__item--collapsed" wire:key="{{ $key }}-ar-{{ $item['key'] ?? $index }}" x-data="collapsible('item-ar-{{ $key }}-{{ $index }}', false, false, 'list-ar-{{ $key }}')" x-on:focusin="focusIn($event)" x-bind:class="{ 'page-list__item--collapsed': ! open }">
                        @include('livewire.admin.pages.partials.list-item-head', ['name' => __($block->itemLabel).' '.($index + 1), 'bodyId' => 'item-ar-'.$key.'-'.$index.'-body', 'idPrefix' => 'head-ar', 'canRemove' => ! $block->fixedItems && count((array) $english) > ((array) $block->default === [] ? 0 : 1)])
                        <div id="item-ar-{{ $key }}-{{ $index }}-body" class="fold" x-bind:class="{ 'fold--open': open, 'fold--settled': settled }" x-bind:inert="! open" inert data-collapsible-body><div class="fold__inner page-list__body">
                        @foreach (PageBlockDefinition::grouped($fieldsHere) as $group => $fields)
                            @php
                                $fields = PageBlockDefinition::laidOut($fields);
                                $pairShort = count(array_filter($fields, fn ($spec) => PageBlockDefinition::isShort($spec))) > 1;
                            @endphp
                            @if ($group !== '')<fieldset class="page-list__group"><legend class="page-list__legend">{{ __($group) }}</legend>@endif
                            <div class="form-row">
                                @foreach ($fields as $field => $spec)
                                    @php
                                        $half = $pairShort && PageBlockDefinition::isShort($spec);
                                        $sharedId = 'ar-'.str_replace('.', '-', $englishModel.$field);
                                    @endphp
                                    @if ($spec['kind'] === PageBlockDefinition::FIELD_TOGGLE)
                                        <x-form.toggle :model="$englishModel.$field" :id="$sharedId" :label="__($spec['label'])" :hint="$field === PageBlockDefinition::HIDDEN ? __('Hidden items stay here and leave the page until you switch this off.') : __('The same in both languages.')" />
                                    @elseif ($spec['kind'] === PageBlockDefinition::FIELD_PLAIN)
                                        <x-form.input @class(['form-col--half' => $half]) :model="$englishModel.$field" :id="$sharedId" :value="$item[$field] ?? ''" :label="__($spec['label'])" :hint="__('The same in both languages.')" :maxlength="$spec['max']" :counter="true" :optional="PageBlockDefinition::isRequired($spec) ? false : null" dir="ltr" />
                                    @else
                                        @php $englishHint = filled($shown[$index][$field] ?? null) ? __('English: :text', ['text' => $shown[$index][$field]]) : null; @endphp
                                        @if (PageBlockDefinition::isLong($spec))
                                            <x-form.textarea :model="$model.'.'.$index.'.'.$field" :value="$value[$index][$field] ?? ''" :label="__($spec['label'])" :hint="$englishHint" :maxlength="$spec['max']" :counter="true" rows="2" :placeholder="$shown[$index][$field] ?? null" dir="rtl" />
                                        @else
                                            <x-form.input @class(['form-col--half' => $half]) :model="$model.'.'.$index.'.'.$field" :value="$value[$index][$field] ?? ''" :label="__($spec['label'])" :hint="$englishHint" :maxlength="$spec['max']" :counter="true" :placeholder="$shown[$index][$field] ?? null" dir="rtl" />
                                        @endif
                                    @endif
                                @endforeach
                            </div>
                            @if ($group !== '')</fieldset>@endif
                        @endforeach
                        </div></div>
                    </li>
                @endforeach
            </ol>
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
