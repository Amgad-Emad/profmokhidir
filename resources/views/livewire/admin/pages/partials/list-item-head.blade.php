{{--
    The heading row of one page-list item, shown while the item is folded: the toggle (chevron and
    "Chapter 1"), the fields edited right here (a chapter's numeral and years, bound to the English
    form, the same in both languages) and Remove. Expects $block, $key, $index, $item, $name, $bodyId,
    $idPrefix and $canRemove.
--}}
@use('App\Support\PageBlockDefinition')
<div class="page-list__head">
    <button type="button" class="page-list__toggle" x-on:click="toggle()" aria-expanded="false" x-bind:aria-expanded="open.toString()" aria-controls="{{ $bodyId }}">
        <x-icon name="chevron-down" class="page-list__chevron size-5" />
        <span class="page-list__name">{{ $name }}</span>
        @if (! empty($item[PageBlockDefinition::HIDDEN]))
            <span class="page-list__flag">{{ __('Hidden') }}</span>
        @endif
    </button>
    @foreach (array_filter($block->editableFields(), fn ($spec) => PageBlockDefinition::isInline($spec)) as $field => $spec)
        @php $inputId = $idPrefix.'-'.$key.'-'.$index.'-'.$field; @endphp
        <label class="sr-only" for="{{ $inputId }}">{{ __($spec['label']) }} ({{ $name }})</label>
        <input id="{{ $inputId }}" type="text" class="page-list__inline-input page-list__inline-input--{{ $field }}" dir="ltr"
               wire:model.live.blur="form.{{ $key }}.{{ $index }}.{{ $field }}" value="{{ $item[$field] ?? '' }}"
               maxlength="{{ $spec['max'] }}" placeholder="{{ $field === 'numeral' ? (\App\Constants\Pages::showsOnlyEntered($pageKey ?? '') ? '' : app(\App\Services\TypesetterService::class)->roman($index + 1)) : __($spec['label']) }}" title="{{ __($spec['label']) }}">
    @endforeach
    @if ($canRemove)
        <x-button variant="danger" size="sm" type="button" class="page-list__remove" wire:click="removeItem('{{ $key }}', {{ $index }})" data-remove-item>
            <x-icon name="trash" class="size-4" /><span class="sr-only">{{ __('Remove') }} {{ $name }}</span>
        </x-button>
    @endif
</div>
