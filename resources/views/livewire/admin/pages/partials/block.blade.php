{{--
    One page block in the editor: the field its type needs, labelled in plain words, with the
    character limit, the placeholders it understands, and "Use the original wording" once it has
    been changed. Required blocks show the original as their placeholder: emptied, they keep it.
--}}
@use('App\Constants\PageBlockType')
@use('App\Support\PageBlockDefinition')
@use('App\Support\PageBlockValue')
@php
    $model = 'form.'.$key;
    $value = $form[$key] ?? null;
    $changed = ! PageBlockValue::isDefault($block, $value);
    $tokens = $block->tokens === [] ? null : 'Placeholders: '.collect($block->tokens)->map(fn ($what, $token) => $token.' ('.$what.')')->implode(', ').'.';
    $guard = $block->required && in_array($block->type, [PageBlockType::ShortText, PageBlockType::LongText], true) ? 'Left empty, the original wording is kept.' : null;
    $hint = trim(implode(' ', array_filter([$block->hint, $tokens, $guard])));
    $placeholder = $block->required && is_string($block->default) && $block->type === PageBlockType::ShortText ? $block->default : null;
@endphp
<div class="page-block form-grid" data-page-block="{{ $key }}" data-block-type="{{ $block->type->value }}" wire:key="block-{{ $key }}">
    @switch($block->type)
        @case(PageBlockType::ShortText)
            @if ($block->max > 160)
                <x-form.textarea :model="$model" :value="$value" :label="$block->label" :hint="$hint ?: null" :maxlength="$block->max" rows="3" :optional="$block->required ? false : null" :placeholder="$placeholder" />
            @else
                <x-form.input :model="$model" :value="$value" :label="$block->label" :hint="$hint ?: null" :maxlength="$block->max" :counter="true" :optional="$block->required ? false : null" :placeholder="$placeholder" />
            @endif
            @break

        @case(PageBlockType::LongText)
            <x-form.editor :model="$model" :label="$block->label" :hint="trim(($hint ? $hint.' ' : '').'Up to '.$block->max.' characters'.($block->inline ? '; bold, italic and links only.' : '.'))" :optional="$block->required ? false : null" />
            @break

        @case(PageBlockType::Link)
            <fieldset class="form-fieldset">
                <legend class="t-label form-label">{{ $block->label }}</legend>
                @if ($block->hint)<p class="form-hint">{{ $block->hint }}</p>@endif
                <div class="mt-4 form-grid">
                    <div class="form-row">
                        <x-form.input class="form-col--half" :model="$model.'.label'" :value="$value['label'] ?? ''" label="Label" :maxlength="$block->max" :counter="true" :optional="false" :placeholder="$block->default['label']" />
                        <x-form.select class="form-col--half" :model="$model.'.route'" label="Goes to" :options="$linkOptions" :optional="false" />
                    </div>
                    <x-form.input :model="$model.'.url'" label="Or a web address" type="url" hint="Only for a page outside this site, beginning with https://. It replaces the page chosen above." />
                </div>
            </fieldset>
            @break

        @case(PageBlockType::List)
            @php $defaults = array_column((array) $block->default, null, 'key'); $count = count($value ?? []); @endphp
            <fieldset class="form-fieldset" data-page-list="{{ $key }}">
                <legend class="t-label form-label">{{ $block->label }}</legend>
                @if ($block->hint)<p class="form-hint">{{ $block->hint }}</p>@endif
                <ol class="page-list mt-4">
                    @foreach ($value ?? [] as $index => $item)
                        @php $name = isset($item['key'], $defaults[$item['key']]) ? ($defaults[$item['key']]['label'] ?? $item['key']) : $block->itemLabel.' '.($index + 1); @endphp
                        <li class="page-list__item" wire:key="{{ $key }}-{{ $item['key'] ?? $index }}">
                            <p class="page-list__name">{{ $name }}</p>
                            <div class="form-row">
                                @foreach ($block->editableFields() as $field => $spec)
                                    @if ($spec['kind'] === PageBlockDefinition::FIELD_ROUTE)
                                        <x-form.select class="form-col--half" :model="$model.'.'.$index.'.'.$field" :label="$spec['label'].' ('.$name.')'" :options="$linkOptions" :optional="false" />
                                    @else
                                        <x-form.input @class(['form-col--half' => count($block->editableFields()) > 1]) :model="$model.'.'.$index.'.'.$field" :value="$item[$field] ?? ''" :label="$spec['label'].' ('.$name.')'" :maxlength="$spec['max']" :counter="true" :optional="false" />
                                    @endif
                                @endforeach
                            </div>
                            @if ($block->sortable || ! $block->fixedItems)
                                <div class="page-list__actions">
                                    @if ($block->sortable)
                                        <x-button variant="quiet" size="sm" type="button" wire:click="moveItem('{{ $key }}', {{ $index }}, -1)" :disabled="$index === 0">Move up<span class="sr-only"> {{ $name }}</span></x-button>
                                        <x-button variant="quiet" size="sm" type="button" wire:click="moveItem('{{ $key }}', {{ $index }}, 1)" :disabled="$index === $count - 1">Move down<span class="sr-only"> {{ $name }}</span></x-button>
                                    @endif
                                    @if (! $block->fixedItems && $count > 1)
                                        <x-button variant="danger" size="sm" type="button" wire:click="removeItem('{{ $key }}', {{ $index }})"><x-icon name="trash" class="size-4" />Remove<span class="sr-only"> {{ $name }}</span></x-button>
                                    @endif
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ol>
                @if (! $block->fixedItems && $count < ($block->maxItems ?? PHP_INT_MAX))
                    <x-button variant="quiet" size="sm" type="button" class="mt-4" wire:click="addItem('{{ $key }}')"><x-icon name="plus" class="size-4" />Add {{ \Illuminate\Support\Str::lower($block->itemLabel) }}</x-button>
                @endif
            </fieldset>
            @break

        @case(PageBlockType::Image)
        @case(PageBlockType::Seo)
            @php $isSeo = $block->type === PageBlockType::Seo; $media = $images[$key] ?? null; @endphp
            @if ($isSeo && $block->fields !== [])
                <x-form.input :model="$model.'.title'" :value="$value['title'] ?? ''" label="Search title" :maxlength="PageBlockDefinition::SEO_TITLE_MAX" :counter="true" hint="Shown as the link in search results and on the browser tab, before the site name." />
                <x-form.textarea :model="$model.'.description'" :value="$value['description'] ?? ''" label="Search description" :maxlength="PageBlockDefinition::SEO_DESCRIPTION_MAX" rows="3" hint="One or two sentences under the link in search results and when the page is shared." />
            @endif
            @if ($isSeo && $block->hint)<p class="form-hint">{{ $block->hint }}</p>@endif
            <x-form.upload :model="'uploads.'.$key" :label="$isSeo ? 'Share image' : $block->label" :hint="($isSeo ? 'Shown when the page is shared on social media; 1200 × 630 pixels works best. ' : ($block->hint ? $block->hint.' ' : '')).$imageHint" :media="$media" :href="$media?->getUrl()" :remove="$media ? 'removeImage(\''.$key.'\')' : null" accept="image/jpeg,image/png,image/webp" :image="true" />
            @if ($media)
                <div class="form-grid" data-image-fields="{{ $key }}">
                    <x-form.input :model="$model.'.alt_text'" label="Alt text" :required="true" :hint="\App\Constants\FormControls::UPLOAD_ALT_PROMPT" />
                    @unless ($isSeo)
                        <x-form.input :model="$model.'.caption'" label="Caption" :required="true" />
                        <div class="form-row">
                            <x-form.input :model="$model.'.place'" label="Place" :required="true" class="form-col--two-thirds" />
                            <x-form.year :model="$model.'.year'" label="Year" :required="true" class="form-col--third" />
                        </div>
                        <fieldset class="form-fieldset" data-focus-point>
                            <legend class="t-label form-label">Crop focus</legend>
                            <p class="form-hint">Where the frame centres when it crops the image. Click the point that must stay in view, or set it in percent.</p>
                            <div class="focus-picker mt-4" x-data x-on:click="const r = $el.getBoundingClientRect(); $wire.set('{{ $model }}.focus_x', Math.round(($event.clientX - r.left) / r.width * 100)); $wire.set('{{ $model }}.focus_y', Math.round(($event.clientY - r.top) / r.height * 100))">
                                <img src="{{ $media->getUrl() }}" alt="" class="focus-picker__image">
                                <span class="focus-picker__mark" aria-hidden="true" style="left: {{ (int) ($value['focus_x'] ?? 50) }}%; top: {{ (int) ($value['focus_y'] ?? 50) }}%"></span>
                            </div>
                            <div class="form-row mt-4">
                                <x-form.input class="form-col--half" :model="$model.'.focus_x'" label="From the left (%)" type="number" min="0" max="100" inputmode="numeric" :optional="false" />
                                <x-form.input class="form-col--half" :model="$model.'.focus_y'" label="From the top (%)" type="number" min="0" max="100" inputmode="numeric" :optional="false" />
                            </div>
                        </fieldset>
                    @endunless
                </div>
            @endif
            @break
    @endswitch

    @if ($changed && $block->type !== PageBlockType::Image && $block->type !== PageBlockType::Seo)
        <p class="page-block__reset"><x-button variant="quiet" size="sm" type="button" wire:click="restoreDefault('{{ $key }}')" data-restore-default="{{ $key }}">Use the original wording<span class="sr-only"> for {{ \Illuminate\Support\Str::lower($block->label) }}</span></x-button></p>
    @endif
</div>
