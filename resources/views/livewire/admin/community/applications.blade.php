@use('App\Constants\CommunityStatus')
@use('App\Livewire\Admin\Community\Applications')
<div>
    <x-admin.page-header :eyebrow="__('Community')" :title="__('Applications and members')" :intro="__('Applications are approved or rejected here; members can be suspended, reinstated or removed. Every decision is emailed to the person and recorded in the audit log.')" />

    <div class="mt-8"><x-admin.flash /></div>

    <x-admin.list-toolbar :toolbar="$toolbar" :items="__('applicants and members')" :placeholder="__('Search names and emails')" :table="$table" />

    <x-admin.list-results :density="$toolbar['density']">
        <x-admin.data-table :table="$table" data-applications>
            <x-slot:empty>
                @if ($filtered)
                    <x-admin.list-empty :title="__('No applications yet')" text="" :filtered="true" />
                @else
                    <x-empty-state :message="match ($current) {
                        CommunityStatus::Pending => __('No applications are waiting for review.'),
                        CommunityStatus::Approved => __('There are no members yet.'),
                        CommunityStatus::Suspended => __('No members are suspended.'),
                        CommunityStatus::Rejected => __('No applications have been rejected.'),
                        default => __('Nobody has applied yet.'),
                    }" />
                @endif
            </x-slot:empty>
        </x-admin.data-table>
    </x-admin.list-results>

    @if ($deciding !== null && $dialog !== null)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-surface-overlay p-4" x-data x-trap.noscroll.inert="true" x-on:keydown.escape.window="$wire.cancelDecision()" data-decision-dialog="{{ $decidingAction }}">
            <form novalidate wire:submit="confirmDecision" role="{{ $dialog['danger'] ? 'alertdialog' : 'dialog' }}" aria-modal="true" aria-labelledby="decision-title" aria-describedby="decision-description" @class(['confirm-dialog max-w-lg', 'confirm-dialog--danger' => $dialog['danger']]) >
                <div class="confirm-dialog__head">
                    @if ($dialog['danger'])
                        <span class="confirm-dialog__icon" data-danger-icon><x-icon name="warning" class="size-6" /></span>
                    @endif
                    <h2 id="decision-title" class="confirm-dialog__title">{{ $dialog['title'] }}</h2>
                </div>
                <p id="decision-description" class="confirm-dialog__body">{{ $deciding->name }} · <span dir="ltr">{{ $deciding->email }}</span>. {{ $dialog['body'] }}</p>
                <div class="mt-6">
                    <x-form.textarea model="note" :label="__('Note to the applicant (optional)')" rows="4" :hint="__('Included in the email and kept in the audit log.')" />
                </div>
                <div class="confirm-dialog__actions">
                    <x-button type="button" variant="quiet" wire:click="cancelDecision" x-init="$nextTick(() => $el.focus())" data-cancel>{{ __('Cancel') }}</x-button>
                    <x-button type="submit" :variant="$dialog['danger'] ? 'danger-filled' : 'primary'" wire:loading.attr="disabled" data-confirm>{{ $dialog['confirm'] }}</x-button>
                </div>
            </form>
        </div>
    @endif
</div>
