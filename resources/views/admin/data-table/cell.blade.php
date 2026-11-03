{{-- One cell of x-admin.data-table, drawn by its column's type (App\Constants\DataTableColumnType). --}}
@php $type = $column->type; @endphp
<td @class(['data-table__cell', 'is-end' => $column->align->value === 'end', 'is-muted' => $cell->muted]) data-cell="{{ $column->key }}" data-cell-type="{{ $type->value }}" data-priority="{{ $column->priority }}" data-label="{{ $column->label }}">
    @switch($type)
        @case(\App\Constants\DataTableColumnType::Status)
            @if ($cell->tone)
                <span class="data-table__status-cell">
                    @if ($cell->toggle)
                        <button type="button" role="switch" class="data-table__switch" aria-checked="{{ $cell->toggle['on'] ? 'true' : 'false' }}" wire:click="{{ $cell->toggle['call'] }}" wire:loading.attr="disabled"
                                title="{{ $cell->toggle['label'] }}" data-status-toggle>
                            <span class="sr-only">{{ $cell->toggle['label'] }}</span>
                        </button>
                    @endif
                    <x-admin.status-chip :tone="$cell->tone" :label="$cell->text" />
                </span>
            @endif
            @break
        @case(\App\Constants\DataTableColumnType::Badges)
            @if ($cell->badges === [])
                <span class="data-table__muted">{{ $cell->text }}</span>
            @else
                <span class="data-table__chips">
                    @foreach ($cell->badges as $badge)
                        <x-admin.status-chip :tone="$badge['tone']" :label="$badge['label']" />
                    @endforeach
                </span>
            @endif
            @break
        @case(\App\Constants\DataTableColumnType::Relative)
        @case(\App\Constants\DataTableColumnType::Date)
            @if ($cell->text !== '')
                <time datetime="{{ $cell->datetime }}"{!! $cell->tooltip ? ' title="'.e($cell->tooltip).'"' : '' !!}>{{ $cell->text }}</time>
            @endif
            @break
        @default
            @if ($cell->text !== '')
                <span @class(['data-table__clip', 'font-mono' => $type === \App\Constants\DataTableColumnType::Mono]) title="{{ $cell->tooltip ?? $cell->text }}" dir="{{ $type === \App\Constants\DataTableColumnType::Mono ? 'ltr' : 'auto' }}">{{ $cell->text }}</span>
            @endif
    @endswitch
</td>
