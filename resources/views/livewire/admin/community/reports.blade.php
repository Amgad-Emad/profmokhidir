@use('App\Livewire\Admin\Community\Reports')
<div>
    <x-admin.page-header :eyebrow="__('Community')" :title="__('Chat reports')" :intro="__('Messages members have reported. Resolve a report, remove the message, or suspend its author; each action is recorded in the audit log.')" />

    <div class="mt-8"><x-admin.flash /></div>

    <x-admin.list-toolbar :toolbar="$toolbar" :items="__('reports')" :placeholder="__('Search reasons, messages and names')" :table="$table" />

    <x-admin.list-results :density="$toolbar['density']">
        <x-admin.data-table :table="$table" data-reports>
            <x-slot:empty>
                @if ($filtered)
                    <x-admin.list-empty :title="__('No reports yet')" text="" :filtered="true" />
                @else
                    <x-empty-state :message="match ($state) { Reports::TAB_OPEN => __('No reports are waiting.'), Reports::TAB_RESOLVED => __('No reports have been resolved yet.'), default => __('Nothing has been reported.') }" />
                @endif
            </x-slot:empty>
        </x-admin.data-table>
    </x-admin.list-results>

    <x-admin.confirm-dialog :show="$this->isConfirming(Reports::ACTION_DELETE)" danger :title="__('Remove this message permanently?')" action="deleteMessage" :confirm-label="__('Remove message')">
        {{ __('Members will see ":placeholder" in its place, its attached files are deleted, and every report about it will be resolved.', ['placeholder' => __(\App\Constants\ChatSettings::REMOVED_PLACEHOLDER)]) }} {{ __(\App\Constants\ConfirmDialog::IRREVERSIBLE) }}
    </x-admin.confirm-dialog>

    @if ($this->isConfirming(Reports::ACTION_SUSPEND))
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-surface-overlay p-4" x-data x-trap.noscroll.inert="true" x-on:keydown.escape.window="$wire.cancelConfirmation()" data-confirm-dialog>
            <form wire:submit="suspendAuthor" role="alertdialog" aria-modal="true" aria-labelledby="suspend-title" aria-describedby="suspend-description" class="confirm-dialog confirm-dialog--danger max-w-lg" novalidate>
                <div class="confirm-dialog__head">
                    <span class="confirm-dialog__icon" data-danger-icon><x-icon name="warning" class="size-6" /></span>
                    <h2 id="suspend-title" class="confirm-dialog__title">{{ __('Suspend the author?') }}</h2>
                </div>
                <p id="suspend-description" class="confirm-dialog__body">{{ __('Their chat access ends immediately, they will be emailed, and this report is resolved.') }}</p>
                <div class="mt-6">
                    <x-form.textarea model="note" :label="__('Note to the member (optional)')" rows="3" />
                </div>
                <div class="confirm-dialog__actions">
                    <x-button type="button" variant="quiet" wire:click="cancelConfirmation" x-init="$nextTick(() => $el.focus())" data-cancel>{{ __('Cancel') }}</x-button>
                    <x-button type="submit" variant="danger-filled" wire:loading.attr="disabled" data-confirm>{{ __('Suspend') }}</x-button>
                </div>
            </form>
        </div>
    @endif
</div>
