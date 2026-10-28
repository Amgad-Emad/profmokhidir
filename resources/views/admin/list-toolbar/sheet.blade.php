{{--
    The filters sheet of x-admin.list-toolbar, for narrow bands: every filter as a group of
    checkboxes or radios with counts (and, on phones, the sort and the density). Choices wait for
    Apply, which sends them in one go; Escape, Close and the backdrop leave the list as it was.
    A side sheet from the end edge on tablets, a bottom sheet on phones.
--}}
<div class="list-sheet-backdrop" x-show="sheet" x-cloak x-on:click.self="closeSheet()" x-on:keydown.escape.stop="closeSheet()" data-filters-sheet>
    <form novalidate class="list-sheet" role="dialog" aria-modal="true" aria-labelledby="list-sheet-title" x-trap.inert.noscroll="sheet" x-on:submit.prevent="apply($el)" x-ref="sheet">
        <div class="list-sheet__head">
            <h2 id="list-sheet-title" class="list-sheet__title">{{ __('Filters') }}</h2>
            <x-button variant="icon" type="button" x-on:click="closeSheet()"><x-icon name="close" /><span class="sr-only">{{ __('Close') }}</span></x-button>
        </div>
        <div class="list-sheet__body">
            @foreach ($toolbar['filters'] as $filter)
                @php $single = $filter['type'] === \App\Constants\ListFilterType::Single->value; @endphp
                <fieldset class="list-sheet__group" data-sheet-filter="{{ $filter['key'] }}">
                    <legend class="list-sheet__legend">{{ $filter['label'] }}</legend>
                    @if ($single)
                        <label class="form-check list-sheet__option">
                            <input class="form-check__box" type="radio" name="filters[{{ $filter['key'] }}]" value="" @checked(! $filter['active']) data-sheet-option>
                            <span class="form-check__text">{{ __('Any') }}</span>
                        </label>
                    @endif
                    @foreach ($filter['options'] as $option)
                        <label class="form-check list-sheet__option">
                            <input class="form-check__box" type="{{ $single ? 'radio' : 'checkbox' }}" name="filters[{{ $filter['key'] }}]{{ $single ? '' : '[]' }}" value="{{ $option['value'] }}" @checked($option['checked']) data-sheet-option>
                            <span class="form-check__text"><span dir="auto">{{ $option['label'] }}</span>@if ($option['count'] !== null)<span class="list-sheet__count">@num($option['count'])</span>@endif</span>
                        </label>
                    @endforeach
                </fieldset>
            @endforeach
            @if ($sorts !== [])
                <fieldset class="list-sheet__group list-sheet__group--phone" data-sheet-sort>
                    <legend class="list-sheet__legend">{{ __('Sort') }}</legend>
                    @foreach ($sorts as $sort)
                        <label class="form-check list-sheet__option">
                            <input class="form-check__box" type="radio" name="sort" value="{{ $sort['column'] === '' ? '' : $sort['column'].':'.$sort['direction'] }}" @checked($sort['active'])>
                            <span class="form-check__text">{{ $sort['label'] }}</span>
                        </label>
                    @endforeach
                </fieldset>
            @endif
            <fieldset class="list-sheet__group list-sheet__group--phone" data-sheet-density>
                <legend class="list-sheet__legend">{{ __('Rows') }}</legend>
                @foreach (\App\Constants\ListDensity::cases() as $density)
                    <label class="form-check list-sheet__option">
                        <input class="form-check__box" type="radio" name="density" value="{{ $density->value }}" @checked($toolbar['density'] === $density->value)>
                        <span class="form-check__text">{{ $density->label() }}</span>
                    </label>
                @endforeach
            </fieldset>
        </div>
        <div class="list-sheet__foot">
            <x-button variant="quiet" type="button" x-on:click="reset($refs.sheet)" data-sheet-reset>{{ __('Clear all') }}</x-button>
            <x-button variant="primary" type="submit" data-sheet-apply>{{ __('Apply') }}</x-button>
        </div>
    </form>
</div>
