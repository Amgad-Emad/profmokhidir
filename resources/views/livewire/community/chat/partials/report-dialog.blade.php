@use('App\Constants\ChatCopy')
@use('App\Constants\ChatSettings')
<div class="fixed inset-0 z-50 flex items-center justify-center bg-surface-overlay p-4" x-show="reporting" x-cloak x-trap.noscroll.inert="reporting" x-on:keydown.escape.window="cancelReport()" data-report-dialog>
    <form role="dialog" aria-modal="true" aria-labelledby="report-title" class="w-full max-w-md rounded-card border border-rule bg-surface-panel p-6 sm:p-8" x-on:submit.prevent="submitReport()">
        <h2 id="report-title" class="font-display text-3xl font-light leading-tight">{{ ChatCopy::REPORT_TITLE }}</h2>
        <p class="mt-3 text-base font-light text-ink-muted">{{ ChatCopy::REPORT_INTRO }}</p>
        <label for="report-reason" class="t-label mt-6 block text-accent-text">Reason</label>
        <textarea id="report-reason" rows="4" class="mt-2 block w-full rounded-card border border-rule-strong bg-surface-field px-4 py-3 text-body text-ink focus:border-focus" x-model="reportReason" maxlength="{{ ChatSettings::REPORT_REASON_MAX_LENGTH }}" required></textarea>
        <p class="mt-2 text-sm text-accent-text" role="alert" x-show="reportError" x-text="reportError"></p>
        <div class="mt-8 flex flex-wrap justify-end gap-3">
            <x-button type="button" variant="outline" x-on:click="cancelReport()">Cancel</x-button>
            <x-button type="submit" x-bind:disabled="reportSending" data-report-submit>Send report</x-button>
        </div>
    </form>
</div>
