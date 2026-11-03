{{--
    The actions of one data table row: inline ones as icon buttons that show on hover or focus at the end
    of the row, and the "…" menu with the rest (and, on touch screens and cards, the inline ones too).
--}}
@php
    $label = $row->title;
    $attributesFor = function (\App\Support\DataTable\RowAction $action, array $classes, bool $withHook): \Illuminate\View\ComponentAttributeBag {
        $attributes = $action->href
            ? ['href' => $action->href, 'class' => \Illuminate\Support\Arr::toCssClasses($classes)]
            : ['type' => 'button', 'class' => \Illuminate\Support\Arr::toCssClasses($classes)];
        if ($withHook && in_array('data-table__action', $classes, true)) {
            $attributes['title'] = $action->label;
        }
        if ($action->href && $action->newTab) {
            $attributes += ['target' => '_blank', 'rel' => 'noopener'];
        }
        if ($action->call) {
            $attributes['wire:click'] = $action->call;
        }
        if ($action->disabled) {
            $attributes['disabled'] = true;
        }
        if ($withHook && $action->hook) {
            $attributes[$action->hook] = true;
        }
        if ($withHook) {
            $attributes += array_map('strval', $action->data);
        }

        return new \Illuminate\View\ComponentAttributeBag($attributes);
    };
@endphp
<div class="data-table__row-end">
    @if ($row->inlineActions() !== [])
        <div class="data-table__row-actions" data-row-actions>
            @foreach ($row->inlineActions() as $action)
                <{{ $action->href ? 'a' : 'button' }} {{ $attributesFor($action, ['data-table__action', 'is-danger' => $action->danger], true) }}>
                    <x-icon :name="$action->icon" class="size-[1.125rem]" /><span class="sr-only">{{ $action->label }}: {{ $label }}{{ $action->newTab ? ' '.__('(opens in a new tab)') : '' }}</span>
                </{{ $action->href ? 'a' : 'button' }}>
            @endforeach
        </div>
    @endif
    @if ($row->actions !== [])
        <x-admin.row-menu :label="$label" class="data-table__menu">
            @foreach ($row->actions as $action)
                <{{ $action->href ? 'a' : 'button' }} {{ $attributesFor($action, ['row-menu__item', 'row-menu__item--danger' => $action->danger, 'row-menu__item--touch' => $action->inline], ! $action->inline) }}>
                    <x-icon :name="$action->icon" class="size-4" />{{ $action->label }}{!! $action->newTab ? '<span class="sr-only"> '.e(__('(opens in a new tab)')).'</span>' : '' !!}
                </{{ $action->href ? 'a' : 'button' }}>
            @endforeach
        </x-admin.row-menu>
    @endif
</div>
