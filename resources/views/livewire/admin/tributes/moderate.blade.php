@use('App\Constants\TributeStatus')
@use('App\Constants\DateFormats')
<div>
    <x-admin.page-header eyebrow="Tributes" title="Review tributes" intro="Tributes appear on the site only after they are approved. One approved tribute can be featured to open the book of remembrance. Email addresses are shown here to moderators and never published." />

    <x-admin.flash />

    <div class="mt-10 flex flex-wrap items-center gap-2 border-b border-rule" role="tablist" aria-label="Tribute status">
        @foreach ($tabs as $value => $tabInfo)
            <button type="button" role="tab" wire:click="showTab('{{ $value }}')" aria-selected="{{ $tab === $value ? 'true' : 'false' }}" class="admin-tab inline-flex items-center gap-2" data-tab="{{ $value }}">
                <x-icon :name="$tabInfo['tone']->icon()" class="size-4" />{{ $tabInfo['label'] }}<span class="text-xs text-ink-muted">{{ $tabInfo['count'] }}</span>
            </button>
        @endforeach
    </div>

    @if ($tributes->isEmpty())
        <x-empty-state :message="match ($current) { TributeStatus::Pending => 'Nothing is waiting for review.', TributeStatus::Approved => 'No tributes have been approved yet.', TributeStatus::Rejected => 'No tributes have been rejected.' }" />
    @else
        {{-- Bulk actions --}}
        <div class="mt-6 flex flex-wrap items-center gap-4 text-sm" data-bulk>
            <x-form.checkbox id="select-all" wire:click="toggleAll" :checked="$pageIds !== [] && array_diff($pageIds, $selected) === []">Select all on this page</x-form.checkbox>
            <span class="font-light text-ink-muted">{{ count($selected) }} selected</span>
            @if ($current !== TributeStatus::Approved)
                <button type="button" wire:click="approveSelected" @disabled($selected === []) class="save-bar__link disabled:opacity-40" data-bulk-approve>Approve selected</button>
            @endif
            @if ($current !== TributeStatus::Rejected)
                <button type="button" wire:click="rejectSelected" @disabled($selected === []) class="save-bar__link save-bar__link--danger disabled:opacity-40" data-bulk-reject>Decline selected</button>
            @endif
        </div>

        <ul class="mt-4 space-y-4" data-tributes>
            @foreach ($tributes as $tribute)
                <li wire:key="tribute-{{ $tribute->getKey() }}" class="rounded-card border border-rule bg-surface-panel p-5" data-tribute="{{ $tribute->getKey() }}">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                        <x-form.checkbox model="selected" live :id="'select-tribute-'.$tribute->getKey()" :value="(string) $tribute->getKey()" class="shrink-0"><span class="sr-only">Select tribute from {{ $tribute->name }}</span></x-form.checkbox>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                                <p class="font-medium text-ink">{{ $tribute->name }}</p>
                                <p class="text-sm font-light text-ink-muted">{{ $tribute->relationship->label() }}@if ($tribute->location) · {{ $tribute->location }}@endif</p>
                                <time datetime="{{ $tribute->created_at->toIso8601String() }}" class="text-sm font-light text-ink-muted">{{ $tribute->created_at->format(DateFormats::LONG) }}</time>
                            </div>
                            <p class="mt-1 font-mono text-xs text-ink-muted" data-email>{{ $tribute->email ?: 'No email given' }}</p>

                            @if ($editingId === (int) $tribute->getKey())
                                <form wire:submit="saveEdit" class="mt-4 space-y-4" novalidate data-edit-form>
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <x-form.input model="edit.name" label="Name" required />
                                        <x-form.input model="edit.location" label="Location" />
                                    </div>
                                    <x-form.textarea model="edit.message" label="Tribute" rows="5" required />
                                    <div class="flex flex-wrap gap-3">
                                        <x-button type="submit" class="px-5 py-2 text-sm">Save changes</x-button>
                                        <x-button type="button" variant="outline" class="px-5 py-2 text-sm" wire:click="cancelEditing">Cancel</x-button>
                                    </div>
                                </form>
                            @else
                                <blockquote class="mt-3 whitespace-pre-line font-display text-xl font-light italic leading-relaxed text-ink">{{ $tribute->message }}</blockquote>
                            @endif
                        </div>
                        <div class="flex shrink-0 flex-wrap items-center gap-x-4 gap-y-2 text-sm sm:flex-col sm:items-end">
                            <x-admin.status-badge :tone="\App\Constants\StatusTone::forTribute($tribute->status)" :label="$tribute->status->label()" />
                            @if ($tribute->isFeatured())
                                <x-admin.status-badge tone="cypress" label="Featured" data-featured-badge />
                            @endif
                            @if (! $tribute->isApproved())
                                <x-button type="button" variant="primary" wire:click="approve({{ $tribute->getKey() }})" class="btn-pill--small" data-approve>Approve<span class="sr-only"> tribute from {{ $tribute->name }}</span></x-button>
                            @endif
                            @if ($tribute->status !== TributeStatus::Rejected)
                                <button type="button" wire:click="decline({{ $tribute->getKey() }})" class="save-bar__link" data-reject>Decline<span class="sr-only"> tribute from {{ $tribute->name }}</span></button>
                            @endif
                            @if ($tribute->isApproved() || $editingId !== (int) $tribute->getKey())
                                <x-admin.row-menu :label="'tribute from '.$tribute->name">
                                    @if ($tribute->isApproved())
                                        @if ($tribute->isFeatured())
                                            <button type="button" wire:click="unfeature({{ $tribute->getKey() }})" class="row-menu__item" data-unfeature><x-icon name="minus" class="size-4" />Remove from featured</button>
                                        @else
                                            <button type="button" wire:click="feature({{ $tribute->getKey() }})" class="row-menu__item" data-feature><x-icon name="check" class="size-4" />Feature on the page</button>
                                        @endif
                                    @endif
                                    @if ($editingId !== (int) $tribute->getKey())
                                        <button type="button" wire:click="startEditing({{ $tribute->getKey() }})" class="row-menu__item" data-edit><x-icon name="feather" class="size-4" />Fix typos</button>
                                    @endif
                                </x-admin.row-menu>
                            @endif
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
        {{ $tributes->links('components.admin.pagination') }}
    @endif

</div>
