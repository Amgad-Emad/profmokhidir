@use('App\Constants\TributeStatus')
<div>
    <x-admin.page-header :eyebrow="__('Tributes')" :title="__('Review tributes')" :intro="__('Tributes appear on the site only after they are approved. One approved tribute can be featured to open the book of remembrance. Email addresses are shown here to moderators and never published.')" />

    <x-admin.flash />

    <x-admin.list-toolbar :toolbar="$toolbar" :items="__('tributes')" :placeholder="__('Search names, places and words')" :selected="count($selected)" :table="$table">
        <x-slot:bulk>
            @if ($current !== TributeStatus::Approved)
                <x-button variant="quiet" size="sm" type="button" wire:click="approveSelected" wire:loading.attr="disabled" data-bulk-approve>{{ __('Approve') }}</x-button>
            @endif
            @if ($current !== TributeStatus::Rejected)
                <x-button variant="danger" size="sm" type="button" wire:click="rejectSelected" wire:loading.attr="disabled" data-bulk-reject>{{ __('Decline') }}</x-button>
            @endif
        </x-slot:bulk>
    </x-admin.list-toolbar>

    <x-admin.list-results :density="$toolbar['density']">
        <x-admin.data-table :table="$table" data-tributes>
            <x-slot:empty>
                @if ($filtered)
                    <x-admin.list-empty :title="__('No tributes yet')" text="" :filtered="true" />
                @else
                    <x-empty-state :message="match ($current) { TributeStatus::Pending => __('Nothing is waiting for review.'), TributeStatus::Approved => __('No tributes have been approved yet.'), TributeStatus::Rejected => __('No tributes have been rejected.'), default => __('No tributes have been left yet.') }" />
                @endif
            </x-slot:empty>
        </x-admin.data-table>
    </x-admin.list-results>

</div>
