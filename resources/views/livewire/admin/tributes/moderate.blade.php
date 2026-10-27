@use('App\Constants\TributeStatus')
@use('App\Constants\DateFormats')
<div>
    <x-admin.page-header :eyebrow="__('Tributes')" :title="__('Review tributes')" :intro="__('Tributes appear on the site only after they are approved. One approved tribute can be featured to open the book of remembrance. Email addresses are shown here to moderators and never published.')" />

    <x-admin.flash />

    <div class="mt-10 flex flex-wrap items-center gap-2 border-b border-rule" role="tablist" aria-label="{{ __('Tribute status') }}">
        @foreach ($tabs as $value => $tabInfo)
            <button type="button" role="tab" wire:click="showTab('{{ $value }}')" aria-selected="{{ $tab === $value ? 'true' : 'false' }}" class="admin-tab inline-flex items-center gap-2" data-tab="{{ $value }}">
                <x-icon :name="$tabInfo['tone']->icon()" class="size-4" />{{ $tabInfo['label'] }}<span class="text-sm text-ink-muted">@num($tabInfo['count'])</span>
            </button>
        @endforeach
    </div>

    @if ($tributes->isEmpty())
        <x-empty-state :message="match ($current) { TributeStatus::Pending => __('Nothing is waiting for review.'), TributeStatus::Approved => __('No tributes have been approved yet.'), TributeStatus::Rejected => __('No tributes have been rejected.') }" />
    @else
        {{-- Bulk actions --}}
        <div class="mt-6 flex flex-wrap items-center gap-4 text-sm" data-bulk>
            <x-form.checkbox id="select-all" wire:click="toggleAll" :checked="$pageIds !== [] && array_diff($pageIds, $selected) === []">{{ __('Select all on this page') }}</x-form.checkbox>
            <span class="font-normal text-ink-muted">{{ __(':count selected', ['count' => app(\App\Services\LocaleService::class)->digits(count($selected))]) }}</span>
            @if ($current !== TributeStatus::Approved)
                <x-button variant="quiet" size="sm" type="button" wire:click="approveSelected" :disabled="$selected === []" data-bulk-approve>{{ __('Approve selected') }}</x-button>
            @endif
            @if ($current !== TributeStatus::Rejected)
                <x-button variant="danger" size="sm" type="button" wire:click="rejectSelected" :disabled="$selected === []" data-bulk-reject>{{ __('Decline selected') }}</x-button>
            @endif
        </div>

        <ul class="mt-4 space-y-4" data-tributes>
            @foreach ($tributes as $tribute)
                <li wire:key="tribute-{{ $tribute->getKey() }}" class="rounded-card border border-rule bg-surface-panel p-5" data-tribute="{{ $tribute->getKey() }}">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                        <x-form.checkbox model="selected" live :id="'select-tribute-'.$tribute->getKey()" :value="(string) $tribute->getKey()" class="shrink-0"><span class="sr-only">{{ __('Select tribute from :name', ['name' => $tribute->name]) }}</span></x-form.checkbox>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                                <p class="font-medium text-ink" dir="auto">{{ $tribute->name }}</p>
                                <p class="text-sm font-normal text-ink-muted">{{ $tribute->relationship->label() }}@if ($tribute->location) · <span dir="auto">{{ $tribute->location }}</span>@endif</p>
                                <time datetime="{{ $tribute->created_at->toIso8601String() }}" class="text-sm font-normal text-ink-muted">{{ $tribute->created_at->translatedFormat(DateFormats::LONG) }}</time>
                            </div>
                            <p class="mt-1 font-mono text-sm text-ink-muted" dir="{{ $tribute->email ? 'ltr' : 'auto' }}" data-email>{{ $tribute->email ?: __('No email given') }}</p>

                            @if ($editingId === (int) $tribute->getKey())
                                <form wire:submit="saveEdit" class="form-grid mt-4" novalidate data-edit-form>
                                    <div class="form-row">
                                        <x-form.input class="form-col--half" model="edit.name" :label="__('Name')" required />
                                        <x-form.input class="form-col--half" model="edit.location" :label="__('Location')" />
                                    </div>
                                    <x-form.textarea model="edit.message" :label="__('Tribute')" rows="5" required />
                                    <div class="flex flex-wrap gap-3">
                                        <x-button type="submit" size="sm">{{ __('Save changes') }}</x-button>
                                        <x-button type="button" variant="quiet" size="sm" wire:click="cancelEditing">{{ __('Cancel') }}</x-button>
                                    </div>
                                </form>
                            @else
                                <blockquote dir="auto" class="mt-3 whitespace-pre-line font-display text-xl font-light italic leading-relaxed text-ink">{{ $tribute->message }}</blockquote>
                            @endif
                        </div>
                        <div class="flex shrink-0 flex-wrap items-center gap-x-4 gap-y-2 text-sm sm:flex-col sm:items-end">
                            <x-admin.status-badge :tone="\App\Constants\StatusTone::forTribute($tribute->status)" :label="$tribute->status->label()" />
                            @if ($tribute->isFeatured())
                                <x-admin.status-badge tone="cypress" :label="__('Featured')" data-featured-badge />
                            @endif
                            @if (! $tribute->isApproved())
                                <x-button type="button" variant="primary" size="sm" wire:click="approve({{ $tribute->getKey() }})" data-approve>{{ __('Approve') }}<span class="sr-only"> {{ __('tribute from :name', ['name' => $tribute->name]) }}</span></x-button>
                            @endif
                            @if ($tribute->status !== TributeStatus::Rejected)
                                <x-button variant="danger" size="sm" type="button" wire:click="decline({{ $tribute->getKey() }})" data-reject>{{ __('Decline') }}<span class="sr-only"> {{ __('tribute from :name', ['name' => $tribute->name]) }}</span></x-button>
                            @endif
                            @if ($tribute->isApproved() || $editingId !== (int) $tribute->getKey())
                                <x-admin.row-menu :label="__('tribute from :name', ['name' => $tribute->name])">
                                    @if ($tribute->isApproved())
                                        @if ($tribute->isFeatured())
                                            <button type="button" wire:click="unfeature({{ $tribute->getKey() }})" class="row-menu__item" data-unfeature><x-icon name="minus" class="size-4" />{{ __('Remove from featured') }}</button>
                                        @else
                                            <button type="button" wire:click="feature({{ $tribute->getKey() }})" class="row-menu__item" data-feature><x-icon name="check" class="size-4" />{{ __('Feature on the page') }}</button>
                                        @endif
                                    @endif
                                    @if ($editingId !== (int) $tribute->getKey())
                                        <button type="button" wire:click="startEditing({{ $tribute->getKey() }})" class="row-menu__item" data-edit><x-icon name="feather" class="size-4" />{{ __('Fix typos') }}</button>
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
