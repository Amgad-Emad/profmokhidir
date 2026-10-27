{{--
    The Arabic tab of a page block: its Arabic words only (destinations, images, crops and years are
    the English tab's). Every field is optional: left empty, Arabic pages show the original wording
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
        @if ($block->max > 160)
            <x-form.textarea :model="$model" :value="$value" :label="__('Arabic: :block', ['block' => __($block->label)])" :hint="$hint" :maxlength="$block->max" rows="3" :placeholder="is_string($shown) ? $shown : null" dir="rtl" />
        @else
            <x-form.input :model="$model" :value="$value" :label="__('Arabic: :block', ['block' => __($block->label)])" :hint="$hint" :maxlength="$block->max" :counter="true" :placeholder="is_string($shown) ? $shown : null" dir="rtl" />
        @endif
        @break

    @case(PageBlockType::LongText)
        <x-form.editor :model="$model" :label="__('Arabic: :block', ['block' => __($block->label)])" :hint="__('Optional. Up to :max characters. Left empty, Arabic pages show the original wording in Arabic.', ['max' => $block->max])" />
        @break

    @case(PageBlockType::Link)
        <x-form.input :model="$model.'.label'" :value="$value['label'] ?? ''" :label="__('Arabic label: :block', ['block' => __($block->label)])" :hint="__('The destination is the one chosen in the English tab.')" :maxlength="$block->max" :counter="true" :placeholder="$shown['label'] ?? null" dir="rtl" />
        @break

    @case(PageBlockType::List)
        <fieldset class="form-fieldset" data-page-list-arabic="{{ $key }}">
            <legend class="t-label form-label">{{ __('Arabic: :block', ['block' => __($block->label)]) }}</legend>
            <p class="form-hint">{{ __('The items, their order and destinations follow the English tab. Fill in the Arabic words; an empty field shows the placeholder.') }}</p>
            <ol class="page-list mt-4">
                @foreach ((array) $english as $index => $item)
                    <li class="page-list__item" wire:key="{{ $key }}-ar-{{ $item['key'] ?? $index }}">
                        <div class="form-row">
                            @foreach ($block->editableFields() as $field => $spec)
                                @continue($spec['kind'] !== PageBlockDefinition::FIELD_TEXT)
                                <x-form.input @class(['form-col--half' => count($block->editableFields()) > 1]) :model="$model.'.'.$index.'.'.$field" :value="$value[$index][$field] ?? ''" :label="__(':field (:item)', ['field' => __($spec['label']), 'item' => $shown[$index][$field] ?? ($index + 1)])" :maxlength="$spec['max']" :counter="true" :placeholder="$shown[$index][$field] ?? null" dir="rtl" />
                            @endforeach
                        </div>
                    </li>
                @endforeach
            </ol>
        </fieldset>
        @break

    @case(PageBlockType::Image)
    @case(PageBlockType::Seo)
        @if ($block->type === PageBlockType::Seo && $block->fields !== [])
            <x-form.input :model="$model.'.title'" :value="$value['title'] ?? ''" :label="__('Arabic search title')" :maxlength="PageBlockDefinition::SEO_TITLE_MAX" :counter="true" :hint="__('Left empty, Arabic pages use the Arabic page heading.')" dir="rtl" />
            <x-form.textarea :model="$model.'.description'" :value="$value['description'] ?? ''" :label="__('Arabic search description')" :maxlength="PageBlockDefinition::SEO_DESCRIPTION_MAX" rows="3" :hint="__('Left empty, Arabic pages use the Arabic introduction.')" dir="rtl" />
        @endif
        @if ($media)
            <x-form.input :model="$model.'.alt_text'" :label="__('Arabic alt text')" :hint="__('Describe the image in Arabic for people who cannot see it. Left empty, the English alt text is read.')" dir="rtl" />
            @if ($block->type === PageBlockType::Image)
                <x-form.input :model="$model.'.caption'" :label="__('Arabic caption')" dir="rtl" />
                <x-form.input :model="$model.'.place'" :label="__('Arabic place')" :hint="__('The year is the English tab’s.')" dir="rtl" />
            @endif
        @endif
        @break
@endswitch
