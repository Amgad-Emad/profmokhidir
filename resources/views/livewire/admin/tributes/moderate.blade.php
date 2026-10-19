@use('App\Constants\TributeStatus')
@use('App\Constants\DateFormats')
<div>
    <x-admin.page-header eyebrow="Tributes" title="Review tributes" intro="Tributes appear on the site only after they are approved. Email addresses are shown here to moderators and never published." />

    <div class="mt-8"><x-admin.flash /></div>

    <div class="flex flex-wrap items-center gap-2 border-b border-stone" role="tablist" aria-label="Tribute status">
        @foreach ($tabs as $value => $tabInfo)
            <button type="button" role="tab" wire:click="showTab('{{ $value }}')" aria-selected="{{ $tab === $value ? 'true' : 'false' }}" class="admin-tab inline-flex items-center gap-2" data-tab="{{ $value }}">
                <x-icon :name="$tabInfo['tone']->icon()" class="size-4" />{{ $tabInfo['label'] }}<span class="font-mono text-xs text-ash">{{ $tabInfo['count'] }}</span>
            </button>
        @endforeach
    </div>

    @if ($tributes->isEmpty())
        <x-empty-state :message="match ($current) { TributeStatus::Pending => 'Nothing is waiting for review.', TributeStatus::Approved => 'No tributes have been approved yet.', TributeStatus::Rejected => 'No tributes have been rejected.' }" />
    @else
        {{-- Bulk actions --}}
        <div class="mt-6 flex flex-wrap items-center gap-4 text-sm" data-bulk>
            <label class="flex items-center gap-2">
                <input type="checkbox" wire:click="toggleAll" @checked($pageIds !== [] && array_diff($pageIds, $selected) === []) class="size-4 rounded-sm border-stone text-evergreen">
                Select all on this page
            </label>
            <span class="font-light text-ash">{{ count($selected) }} selected</span>
            @if ($current !== TributeStatus::Approved)
                <button type="button" wire:click="approveSelected" @disabled($selected === []) class="text-cypress underline-offset-4 hover:underline disabled:opacity-40" data-bulk-approve>Approve selected</button>
            @endif
            @if ($current !== TributeStatus::Rejected)
                <button type="button" wire:click="confirmRejectSelected" @disabled($selected === []) class="text-bronze underline-offset-4 hover:underline disabled:opacity-40" data-bulk-reject>Reject selected</button>
            @endif
        </div>

        <ul class="mt-4 space-y-4" data-tributes>
            @foreach ($tributes as $tribute)
                <li wire:key="tribute-{{ $tribute->getKey() }}" class="rounded-card border border-stone bg-ivory p-5" data-tribute="{{ $tribute->getKey() }}">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                        <input type="checkbox" value="{{ $tribute->getKey() }}" wire:model.live="selected" class="mt-1 size-4 shrink-0 rounded-sm border-stone text-evergreen" aria-label="Select tribute from {{ $tribute->name }}">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                                <p class="font-medium text-evergreen">{{ $tribute->name }}</p>
                                <p class="text-sm font-light text-ash">{{ $tribute->relationship->label() }}@if ($tribute->location) · {{ $tribute->location }}@endif</p>
                                <time datetime="{{ $tribute->created_at->toIso8601String() }}" class="text-sm font-light text-ash">{{ $tribute->created_at->format(DateFormats::LONG) }}</time>
                            </div>
                            <p class="mt-1 font-mono text-xs text-ash" data-email>{{ $tribute->email ?: 'No email given' }}</p>

                            @if ($editingId === (int) $tribute->getKey())
                                <form wire:submit="saveEdit" class="mt-4 space-y-4" novalidate data-edit-form>
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <x-admin.field name="edit.name" label="Name" required />
                                        <x-admin.field name="edit.location" label="Location" />
                                    </div>
                                    <x-admin.textarea-field name="edit.message" label="Tribute" rows="5" required />
                                    <div class="flex flex-wrap gap-3">
                                        <x-button type="submit" class="px-5 py-2 text-sm">Save changes</x-button>
                                        <x-button type="button" variant="outline" class="px-5 py-2 text-sm" wire:click="cancelEditing">Cancel</x-button>
                                    </div>
                                </form>
                            @else
                                <blockquote class="mt-3 whitespace-pre-line font-display text-xl font-light italic leading-relaxed text-evergreen">{{ $tribute->message }}</blockquote>
                            @endif
                        </div>
                        <div class="flex shrink-0 flex-wrap items-center gap-x-4 gap-y-2 text-sm sm:flex-col sm:items-end">
                            <x-admin.status-badge :tone="\App\Constants\StatusTone::forTribute($tribute->status)" :label="$tribute->status->label()" />
                            @if (! $tribute->isApproved())
                                <button type="button" wire:click="approve({{ $tribute->getKey() }})" class="text-cypress underline-offset-4 hover:underline" data-approve>Approve<span class="sr-only"> tribute from {{ $tribute->name }}</span></button>
                            @endif
                            @if ($tribute->status !== TributeStatus::Rejected)
                                <button type="button" wire:click="confirm('reject', {{ $tribute->getKey() }})" class="text-bronze underline-offset-4 hover:underline" data-reject>Reject<span class="sr-only"> tribute from {{ $tribute->name }}</span></button>
                            @endif
                            @if ($editingId !== (int) $tribute->getKey())
                                <button type="button" wire:click="startEditing({{ $tribute->getKey() }})" class="link-meta" data-edit>Fix typos<span class="sr-only"> in tribute from {{ $tribute->name }}</span></button>
                            @endif
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
        {{ $tributes->links('components.admin.pagination') }}
    @endif

    <x-admin.confirm-dialog :show="$this->isConfirming('reject')" title="Reject this tribute?" action="reject" confirm-label="Reject">
        It will not appear on the site. You can approve it later from the Rejected tab.
    </x-admin.confirm-dialog>
    <x-admin.confirm-dialog :show="$this->isConfirming('bulk-reject')" title="Reject the selected tributes?" action="rejectSelected" confirm-label="Reject selected">
        {{ count($selected) }} {{ \Illuminate\Support\Str::plural('tribute', count($selected)) }} will be rejected. They can be approved later from the Rejected tab.
    </x-admin.confirm-dialog>
</div>
