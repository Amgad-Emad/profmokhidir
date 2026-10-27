{{-- Admin › Pages: the public pages whose words and images editors change, in site order. --}}
@use('App\Constants\StatusTone')
<div>
    <x-admin.page-header :eyebrow="__('Content')" :title="__('Pages')" :intro="__('The fixed words and images of each public page: headings, introductions, buttons, empty-page lines, the community rules, the error pages, the header and footer, and how each page appears in search and when shared. Records such as publications and tributes have their own screens.')" />
    <x-admin.flash />

    <div class="admin-table-wrap mt-10">
        <table class="admin-table w-full text-start text-sm" data-pages-table>
            <caption class="sr-only">{{ __('Pages') }}</caption>
            <thead>
                <tr>
                    <th scope="col">{{ __('Page') }}</th>
                    <th scope="col">{{ __('Status') }}</th>
                    <th scope="col">{{ __('Last saved') }}</th>
                    <th scope="col"><span class="sr-only">{{ __('Actions') }}</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr wire:key="page-{{ $row['page']->key }}" data-page-row="{{ $row['page']->key }}">
                        <td>
                            <a href="{{ $row['editUrl'] }}" class="font-medium text-ink">{{ __($row['definition']['title']) }}</a>
                            <p class="mt-1 max-w-xl font-normal text-ink-muted">{{ __($row['definition']['summary']) }}</p>
                        </td>
                        <td>
                            <div class="flex flex-wrap gap-2">
                                <x-admin.status-badge :tone="StatusTone::forContent($row['page']->status)" :label="$row['page']->status->label()" />
                                @if ($row['hasDraft'])
                                    <x-admin.status-badge :tone="StatusTone::Bronze" :label="__('Unpublished changes')" data-has-draft />
                                @endif
                                @if ($row['needsArabic'])
                                    <x-admin.status-badge :tone="StatusTone::Bronze" :label="__('Arabic missing')" data-needs-arabic />
                                @endif
                            </div>
                        </td>
                        <td class="whitespace-nowrap font-normal text-ink-muted">
                            @if ($row['page']->updated_at)
                                <time datetime="{{ $row['page']->updated_at->toIso8601String() }}">{{ $row['page']->updated_at->diffForHumans() }}</time>
                            @endif
                        </td>
                        <td class="text-end">
                            <x-button variant="quiet" size="sm" :href="$row['editUrl']">{{ __('Edit') }}<span class="sr-only"> {{ __($row['definition']['title']) }}</span></x-button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
