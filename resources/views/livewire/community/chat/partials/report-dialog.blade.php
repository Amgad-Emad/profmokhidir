@use('App\Constants\ChatCopy')
@use('App\Constants\ChatSettings')
<div class="fixed inset-0 z-50 flex items-center justify-center bg-surface-overlay p-4" x-show="reporting" x-cloak x-trap.noscroll.inert="reporting" x-on:keydown.escape.window="cancelReport()" data-report-dialog>
    <form novalidate role="alertdialog" aria-modal="true" aria-labelledby="report-title" aria-describedby="report-description" class="confirm-dialog confirm-dialog--danger" x-on:submit.prevent="submitReport()">
        <div class="confirm-dialog__head">
            <span class="confirm-dialog__icon" data-danger-icon><x-icon name="flag" class="size-5" /></span>
            <h2 id="report-title" class="confirm-dialog__title">{{ __(ChatCopy::REPORT_TITLE) }}</h2>
        </div>
        <p id="report-description" class="confirm-dialog__body">{{ __(ChatCopy::REPORT_INTRO) }}</p>
        <x-form.textarea name="reason" id="report-reason" :label="__(ChatCopy::REPORT_REASON_LABEL)" required rows="4" class="mt-6"
                         :maxlength="ChatSettings::REPORT_REASON_MAX_LENGTH"
                         :control="['dir' => 'auto', 'x-model' => 'reportReason']" />
        <p class="form-message form-error" role="alert" x-show="reportError" x-cloak><x-icon name="warning" class="mt-1 size-4 shrink-0" /><span x-text="reportError"></span></p>
        <div class="confirm-dialog__actions">
            <x-button type="button" variant="quiet" x-on:click="cancelReport()" data-cancel>{{ __(\App\Constants\ConfirmDialog::CANCEL) }}</x-button>
            <x-button type="submit" variant="danger-filled" x-bind:disabled="reportSending" data-report-submit><x-icon name="flag" class="size-4" />{{ __('Send report') }}</x-button>
        </div>
    </form>
</div>
