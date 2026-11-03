@use('App\Livewire\Admin\ContentIndex')
<div>
    @php
        $plural = __($type['plural']);
        $singularLower = mb_strtolower(__($type['singular']));
        $pluralLower = mb_strtolower($plural);
    @endphp
    <x-admin.page-header :eyebrow="__(\App\Constants\AdminNavigation::groups()[\App\Constants\AdminNavigation::get($type['nav'])['group']])" :title="$plural" />

    <x-admin.flash />
    @error('publish')
        <x-alert tone="danger" role="alert" class="mt-8">{{ $message }}</x-alert>
    @enderror

    <x-admin.list-toolbar :toolbar="$toolbar" :items="$pluralLower" :placeholder="__('Search :items', ['items' => $pluralLower])" :table="$table"
                          :create-url="$createUrl" :create-label="__('New')" :selected="$trash ? 0 : count($selected)">
        <x-slot:bulk>
            <x-button variant="quiet" size="sm" type="button" wire:click="bulkPublish" wire:loading.attr="disabled" data-bulk="publish">{{ __('Publish') }}</x-button>
            <x-button variant="quiet" size="sm" type="button" wire:click="bulkUnpublish" wire:loading.attr="disabled" data-bulk="unpublish">{{ __('Unpublish') }}</x-button>
            <x-button variant="danger" size="sm" type="button" wire:click="bulkTrash" wire:loading.attr="disabled" data-bulk="trash"><x-icon name="trash" class="size-4" />{{ __('Move to trash') }}</x-button>
        </x-slot:bulk>
    </x-admin.list-toolbar>

    <x-admin.list-results :density="$toolbar['density']">
        <x-admin.data-table :table="$table" data-view="{{ $trash ? 'trash' : 'active' }}">
            <x-slot:empty>
                @if ($trash && ! $hasFilters)
                    <x-empty-state :message="__('The trash is empty. Anything moved to the trash waits here until it is restored.')" />
                @else
                    <x-admin.list-empty :title="__('No :items yet', ['items' => $pluralLower])" :text="__($emptyText)" :create-url="$createUrl" :create-label="__('Add the first :type', ['type' => $singularLower])" :filtered="$hasFilters" />
                @endif
            </x-slot:empty>
        </x-admin.data-table>
    </x-admin.list-results>

    @if ($purging)
        <x-admin.confirm-dialog :show="true" danger :title="$purging['title']" action="purge" :confirm-label="$purging['confirm']" data-purge-dialog>
            {{ $purging['body'] }}
        </x-admin.confirm-dialog>
    @endif
</div>
