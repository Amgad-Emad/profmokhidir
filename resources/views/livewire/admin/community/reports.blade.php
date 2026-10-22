@use('App\Constants\DateFormats')
@use('App\Constants\StatusTone')
@use('App\Livewire\Admin\Community\Reports')
<div>
    <x-admin.page-header eyebrow="Community" title="Chat reports" intro="Messages members have reported. Resolve a report, remove the message, or suspend its author; each action is recorded in the audit log." />

    <div class="mt-8"><x-admin.flash /></div>

    <div class="flex flex-wrap items-center gap-2 border-b border-rule" role="tablist" aria-label="Report state">
        <button type="button" role="tab" wire:click="showTab('{{ Reports::TAB_OPEN }}')" aria-selected="{{ $tab === Reports::TAB_OPEN ? 'true' : 'false' }}" class="admin-tab inline-flex items-center gap-2" data-tab="open">
            <x-icon name="clock" class="size-4" />Open<span class="font-mono text-xs text-ink-muted">{{ $openCount }}</span>
        </button>
        <button type="button" role="tab" wire:click="showTab('{{ Reports::TAB_RESOLVED }}')" aria-selected="{{ $tab === Reports::TAB_RESOLVED ? 'true' : 'false' }}" class="admin-tab inline-flex items-center gap-2" data-tab="resolved">
            <x-icon name="check" class="size-4" />Resolved
        </button>
    </div>

    @if ($reports->isEmpty())
        <x-empty-state :message="$tab === Reports::TAB_OPEN ? 'No reports are waiting.' : 'No reports have been resolved yet.'" />
    @else
        <ul class="mt-6 space-y-4" data-reports>
            @foreach ($reports as $report)
                @php $message = $report->message; @endphp
                <li wire:key="report-{{ $report->getKey() }}" class="rounded-card border border-rule bg-surface-panel p-5" data-report="{{ $report->getKey() }}">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 text-sm">
                                <p class="font-medium text-ink">Reported by {{ $report->reporter?->name ?? 'a former member' }}</p>
                                <time datetime="{{ $report->created_at->toIso8601String() }}" class="font-light text-ink-muted">{{ $report->created_at->format(DateFormats::LONG) }}</time>
                                @if ($message?->channel)
                                    <span class="font-light text-ink-muted">in {{ $message->channel->name }}</span>
                                @endif
                            </div>
                            <p class="mt-2 text-base font-light text-ink" data-reason>{{ $report->reason ?: 'No reason given.' }}</p>

                            <div class="mt-4 rounded-card border border-rule bg-surface-sunken p-4">
                                <p class="t-label text-accent-text">Message by {{ $message?->user?->name ?? 'a former member' }}</p>
                                @if ($message === null || $message->trashed())
                                    <p class="mt-2 font-display text-lg font-light italic text-ink-muted">{{ \App\Constants\ChatSettings::REMOVED_PLACEHOLDER }}</p>
                                @else
                                    <p class="mt-2 whitespace-pre-wrap text-base font-light text-ink" data-message>{{ $message->body }}</p>
                                @endif
                            </div>
                            @if ($report->isResolved())
                                <p class="mt-3 text-sm font-light text-ink-muted">Resolved <x-date :date="$report->resolved_at" />@if ($report->resolver) by {{ $report->resolver->name }}@endif</p>
                            @endif
                        </div>
                        <div class="flex shrink-0 flex-wrap items-center gap-x-4 gap-y-2 text-sm lg:flex-col lg:items-end">
                            <x-admin.status-badge :tone="$report->isResolved() ? StatusTone::Cypress : StatusTone::Bronze" :label="$report->isResolved() ? 'Resolved' : 'Open'" />
                            @if (! $report->isResolved())
                                <button type="button" wire:click="resolve({{ $report->getKey() }})" class="text-success-tone underline-offset-4 hover:underline" data-resolve>Resolve</button>
                                @if ($message !== null && ! $message->trashed())
                                    <button type="button" wire:click="confirm('{{ Reports::ACTION_DELETE }}', {{ $report->getKey() }})" class="text-danger-tone underline-offset-4 hover:underline" data-delete-message>Remove message</button>
                                @endif
                                @if ($message?->user !== null && $message->user->community_status->canChat())
                                    <button type="button" wire:click="confirm('{{ Reports::ACTION_SUSPEND }}', {{ $report->getKey() }})" class="text-ink-muted underline-offset-4 hover:underline" data-suspend-author>Suspend author</button>
                                @endif
                            @endif
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
        {{ $reports->links('components.admin.pagination') }}
    @endif

    <x-admin.confirm-dialog :show="$this->isConfirming(Reports::ACTION_DELETE)" title="Remove this message?" action="deleteMessage" confirm-label="Remove message">
        Members will see "{{ \App\Constants\ChatSettings::REMOVED_PLACEHOLDER }}" in its place and every report about it will be resolved.
    </x-admin.confirm-dialog>

    @if ($this->isConfirming(Reports::ACTION_SUSPEND))
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-surface-overlay p-4" x-data x-trap.noscroll.inert="true" x-on:keydown.escape.window="$wire.cancelConfirmation()" data-confirm-dialog>
            <form wire:submit="suspendAuthor" role="dialog" aria-modal="true" aria-labelledby="suspend-title" class="w-full max-w-lg rounded-card border border-rule bg-surface-panel p-6 sm:p-8" novalidate>
                <h2 id="suspend-title" class="font-display text-3xl font-light leading-tight">Suspend the author?</h2>
                <p class="mt-3 text-base font-light text-ink">Their chat access ends immediately, they will be emailed, and this report is resolved.</p>
                <div class="mt-6">
                    <x-admin.textarea-field name="note" label="Note to the member (optional)" rows="3" />
                </div>
                <div class="mt-8 flex flex-wrap justify-end gap-3">
                    <x-button type="button" variant="outline" wire:click="cancelConfirmation">Cancel</x-button>
                    <x-button type="submit" wire:loading.attr="disabled" data-confirm>Suspend</x-button>
                </div>
            </form>
        </div>
    @endif
</div>
