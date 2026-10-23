@use('App\Constants\ChatCopy')
@use('App\Constants\ChatSettings')
<div class="fixed inset-0 z-50 flex items-center justify-center bg-surface-overlay p-4" x-show="reporting" x-cloak x-trap.noscroll.inert="reporting" x-on:keydown.escape.window="cancelReport()" data-report-dialog>
    <form novalidate role="dialog" aria-modal="true" aria-labelledby="report-title" class="w-full max-w-md rounded-card border border-rule bg-surface-panel p-6 sm:p-8" x-on:submit.prevent="submitReport()">
        <h2 id="report-title" class="font-display text-3xl font-light leading-tight">{{ ChatCopy::REPORT_TITLE }}</h2>
        <p class="mt-3 text-base font-light text-ink-muted">{{ ChatCopy::REPORT_INTRO }}</p>
        <x-form.textarea name="reason" id="report-reason" :label="ChatCopy::REPORT_REASON_LABEL" required rows="4" class="mt-6"
                         :maxlength="ChatSettings::REPORT_REASON_MAX_LENGTH"
                         :control="['x-model' => 'reportReason']" />
        <p class="form-message form-error" role="alert" x-show="reportError" x-cloak><x-icon name="warning" class="mt-1 size-4 shrink-0" /><span x-text="reportError"></span></p>
        <div class="mt-8 flex flex-wrap justify-end gap-3">
            <x-button type="button" variant="outline" x-on:click="cancelReport()">Cancel</x-button>
            <x-button type="submit" x-bind:disabled="reportSending" data-report-submit>Send report</x-button>
        </div>
    </form>
</div>
