{{-- Under a report's row: the reported message with its files, and when it was resolved (App\Livewire\Admin\Community\Reports). --}}
<div class="rounded-card border border-rule bg-surface-sunken p-4">
    <p class="t-label text-accent-text">{{ __('Message by :name', ['name' => $message?->user?->name ?? __('a former member')]) }}</p>
    @if ($message === null || $message->trashed())
        <p class="mt-2 font-display text-lg font-light italic text-ink-muted">{{ __(\App\Constants\ChatSettings::REMOVED_PLACEHOLDER) }}</p>
    @else
        <p class="mt-2 whitespace-pre-wrap text-base font-normal text-ink" dir="auto" data-message>{{ $message->body }}</p>
        {{-- A report covers the message's files too: each opens through the same signed, access-checked link members use. --}}
        @php $files = \App\Support\ChatAttachmentData::collection($message); @endphp
        @if ($files !== [])
            <ul class="mt-3 space-y-1 text-sm" aria-label="{{ __(\App\Constants\ChatCopy::ATTACHMENTS_LABEL) }}" data-report-attachments>
                @foreach ($files as $file)
                    <li class="flex flex-wrap items-baseline gap-x-2">
                        <a href="{{ $file['url'] }}" class="text-ink underline decoration-accent-line underline-offset-4" target="_blank" rel="noopener" dir="auto">{{ $file['name'] }}</a>
                        <span class="font-normal text-ink-muted">{{ $file['type'] }} · {{ $file['size_label'] }}{{ $file['alt'] !== '' ? ' · “'.$file['alt'].'”' : '' }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    @endif
</div>
@if ($report->isResolved())
    <p class="mt-3 text-sm font-normal text-ink-muted">{{ __('Resolved') }} <x-date :date="$report->resolved_at" />{{ $report->resolver ? ' '.__('by :name', ['name' => $report->resolver->name]) : '' }}</p>
@endif
