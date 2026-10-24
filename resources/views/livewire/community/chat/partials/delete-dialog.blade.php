@use('App\Constants\ChatCopy')
@use('App\Constants\ConfirmDialog')
{{-- Deleting a message cannot be undone, so the trash button asks here first: the danger icon, what happens, Cancel focused, the filled danger button. --}}
<div class="fixed inset-0 z-50 flex items-center justify-center bg-surface-overlay p-4" x-show="deleting !== null" x-cloak x-trap.noscroll.inert="deleting !== null" x-on:keydown.escape.window="if (deleting !== null) cancelDelete(deleteTarget)" data-delete-dialog>
    <div role="alertdialog" aria-modal="true" aria-labelledby="delete-title" aria-describedby="delete-description" class="confirm-dialog confirm-dialog--danger">
        <div class="confirm-dialog__head">
            <span class="confirm-dialog__icon" data-danger-icon><x-icon name="warning" class="size-6" /></span>
            <h2 id="delete-title" class="confirm-dialog__title">{{ ChatCopy::DELETE_CONFIRM }}</h2>
        </div>
        <p id="delete-description" class="confirm-dialog__body">{{ ChatCopy::DELETE_CONSEQUENCE }} {{ ConfirmDialog::IRREVERSIBLE }}</p>
        <div class="confirm-dialog__actions">
            <x-button type="button" variant="quiet" x-on:click="cancelDelete(deleteTarget)" x-effect="if (deleting !== null) $nextTick(() => $el.focus())" data-cancel>{{ ConfirmDialog::CANCEL }}</x-button>
            <x-button type="button" variant="danger-filled" x-on:click="remove(deleteTarget)" data-delete-confirm><x-icon name="trash" class="size-4" />{{ ChatCopy::DELETE_LABEL }}</x-button>
        </div>
    </div>
</div>
