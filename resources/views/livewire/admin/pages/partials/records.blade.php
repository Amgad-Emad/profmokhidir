{{--
    The page's own records (App\Services\PageRecordsService) at the foot of its editor, in the order
    the page shows them: title, year and status, each opening its form, with Add and the full list
    (search, filters, bulk actions) one click away.
--}}
<x-admin.form-section id="page-records" :title="$records['plural']" :collapsed="true" wire:key="section-page-records">
    <p class="form-hint">{{ __('These are the :items the page shows, in its order. Open one to change it, or add a new one.', ['items' => \Illuminate\Support\Str::lower($records['plural'])]) }}</p>

    <div class="mt-4 flex flex-wrap items-center gap-3">
        @if ($records['createUrl'])
            <x-button :href="$records['createUrl']" size="sm" data-page-records-add><x-icon name="plus" class="size-4" />{{ __('Add :item', ['item' => \Illuminate\Support\Str::lower($records['singular'])]) }}</x-button>
        @endif
        <x-button :href="$records['indexUrl']" variant="quiet" size="sm" data-page-records-index>{{ __('Open the full list') }}</x-button>
    </div>

    @if ($records['rows'] === [])
        <x-empty-state :message="__('Nothing has been added here yet.')" class="py-8" />
    @else
        <ol class="page-records mt-6" data-page-records="{{ $records['type'] }}">
            @foreach ($records['rows'] as $row)
                <li class="page-records__row">
                    <a href="{{ $row['editUrl'] }}" class="page-records__title" dir="auto">{{ $row['title'] }}</a>
                    <span class="page-records__meta">
                        @if ($row['year'])<span class="font-mono text-sm text-ink-muted">{{ $row['year'] }}</span>@endif
                        <x-admin.status-badge :tone="$row['tone']" :label="$row['statusLabel']" />
                    </span>
                </li>
            @endforeach
        </ol>
        @if ($records['total'] > count($records['rows']))
            <p class="form-hint mt-4">{{ __('Showing :shown of :total. Open the full list to see the rest.', ['shown' => count($records['rows']), 'total' => $records['total']]) }}</p>
        @endif
    @endif
</x-admin.form-section>
