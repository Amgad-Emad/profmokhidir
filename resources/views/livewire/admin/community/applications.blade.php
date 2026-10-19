@use('App\Constants\CommunityStatus')
@use('App\Constants\DateFormats')
@use('App\Constants\StatusTone')
@use('App\Livewire\Admin\Community\Applications')
<div>
    <x-admin.page-header eyebrow="Community" title="Applications and members" intro="Applications are approved or rejected here; members can be suspended, reinstated or removed. Every decision is emailed to the person and recorded in the audit log." />

    <div class="mt-8"><x-admin.flash /></div>

    <div class="flex flex-wrap items-center gap-2 border-b border-stone" role="tablist" aria-label="Community status">
        @foreach ($tabs as $value => $tabInfo)
            <button type="button" role="tab" wire:click="showTab('{{ $value }}')" aria-selected="{{ $tab === $value ? 'true' : 'false' }}" class="admin-tab inline-flex items-center gap-2" data-tab="{{ $value }}">
                <x-icon :name="$tabInfo['tone']->icon()" class="size-4" />{{ $tabInfo['label'] }}<span class="font-mono text-xs text-ash">{{ $tabInfo['count'] }}</span>
            </button>
        @endforeach
    </div>

    @if ($users->isEmpty())
        <x-empty-state :message="match ($current) {
            CommunityStatus::Pending => 'No applications are waiting for review.',
            CommunityStatus::Approved => 'There are no members yet.',
            CommunityStatus::Suspended => 'No members are suspended.',
            default => 'No applications have been rejected.',
        }" />
    @else
        <ul class="mt-6 space-y-4" data-applications>
            @foreach ($users as $user)
                <li wire:key="user-{{ $user->getKey() }}" class="rounded-card border border-stone bg-ivory p-5" data-user="{{ $user->getKey() }}">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                                <p class="font-medium text-evergreen">{{ $user->name }}</p>
                                <p class="font-mono text-xs text-ash">{{ $user->email }}</p>
                                @if ($user->email_verified_at === null)
                                    <x-admin.status-badge :tone="StatusTone::Bronze" label="Email unverified" />
                                @endif
                            </div>
                            <dl class="mt-4 grid gap-x-8 gap-y-3 text-sm sm:grid-cols-2">
                                <div>
                                    <dt class="t-label text-bronze">Affiliation</dt>
                                    <dd class="mt-1 font-light text-evergreen">{{ $user->affiliation ?: 'Not given' }}</dd>
                                </div>
                                <div>
                                    <dt class="t-label text-bronze">Academic field</dt>
                                    <dd class="mt-1 font-light text-evergreen">{{ $user->academic_field ?: 'Not given' }}</dd>
                                </div>
                                <div class="sm:col-span-2">
                                    <dt class="t-label text-bronze">Reason for joining</dt>
                                    <dd class="mt-1 whitespace-pre-line font-light text-evergreen">{{ $user->reason_for_joining ?: 'Not given' }}</dd>
                                </div>
                                <div>
                                    <dt class="t-label text-bronze">Applied</dt>
                                    <dd class="mt-1 font-light text-evergreen"><x-date :date="$user->created_at" /></dd>
                                </div>
                                @if ($user->reviewed_at)
                                    <div>
                                        <dt class="t-label text-bronze">Reviewed</dt>
                                        <dd class="mt-1 font-light text-evergreen"><x-date :date="$user->reviewed_at" />@if ($user->reviewer) by {{ $user->reviewer->name }}@endif</dd>
                                    </div>
                                @endif
                                @if ($user->review_note)
                                    <div class="sm:col-span-2">
                                        <dt class="t-label text-bronze">Note</dt>
                                        <dd class="mt-1 whitespace-pre-line font-light text-evergreen">{{ $user->review_note }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </div>
                        <div class="flex shrink-0 flex-wrap items-center gap-x-4 gap-y-2 text-sm lg:flex-col lg:items-end">
                            <x-admin.status-badge :tone="StatusTone::forCommunity($user->community_status)" :label="$user->community_status->label()" />
                            @if ($user->community_status === CommunityStatus::Pending || $user->community_status === CommunityStatus::Rejected)
                                <button type="button" wire:click="decide('{{ Applications::ACTION_APPROVE }}', {{ $user->getKey() }})" class="text-cypress underline-offset-4 hover:underline" data-approve>Approve<span class="sr-only"> {{ $user->name }}</span></button>
                            @endif
                            @if ($user->community_status === CommunityStatus::Pending)
                                <button type="button" wire:click="decide('{{ Applications::ACTION_REJECT }}', {{ $user->getKey() }})" class="text-bronze underline-offset-4 hover:underline" data-reject>Reject<span class="sr-only"> {{ $user->name }}</span></button>
                            @endif
                            @if ($user->community_status === CommunityStatus::Approved)
                                <button type="button" wire:click="decide('{{ Applications::ACTION_SUSPEND }}', {{ $user->getKey() }})" class="text-bronze underline-offset-4 hover:underline" data-suspend>Suspend<span class="sr-only"> {{ $user->name }}</span></button>
                            @endif
                            @if ($user->community_status === CommunityStatus::Suspended)
                                <button type="button" wire:click="decide('{{ Applications::ACTION_REINSTATE }}', {{ $user->getKey() }})" class="text-cypress underline-offset-4 hover:underline" data-reinstate>Reinstate<span class="sr-only"> {{ $user->name }}</span></button>
                            @endif
                            @if (in_array($user->community_status, [CommunityStatus::Approved, CommunityStatus::Suspended], true))
                                <button type="button" wire:click="decide('{{ Applications::ACTION_REMOVE }}', {{ $user->getKey() }})" class="text-ash underline-offset-4 hover:underline" data-remove>Remove<span class="sr-only"> {{ $user->name }}</span></button>
                            @endif
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
        {{ $users->links('components.admin.pagination') }}
    @endif

    @if ($deciding !== null && $dialog !== null)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-evergreen/70 p-4" x-data x-trap.noscroll.inert="true" x-on:keydown.escape.window="$wire.cancelDecision()" data-decision-dialog="{{ $decidingAction }}">
            <form wire:submit="confirmDecision" role="dialog" aria-modal="true" aria-labelledby="decision-title" aria-describedby="decision-description" class="w-full max-w-lg rounded-card border border-stone bg-ivory p-6 sm:p-8" novalidate>
                <h2 id="decision-title" class="font-display text-3xl font-light leading-tight">{{ $dialog['title'] }}</h2>
                <p id="decision-description" class="mt-3 text-base font-light text-evergreen">{{ $deciding->name }} · {{ $deciding->email }}. {{ $dialog['body'] }}</p>
                <div class="mt-6">
                    <x-admin.textarea-field name="note" label="Note to the applicant (optional)" rows="4" hint="Included in the email and kept in the audit log." />
                </div>
                <div class="mt-8 flex flex-wrap justify-end gap-3">
                    <x-button type="button" variant="outline" wire:click="cancelDecision">Cancel</x-button>
                    <x-button type="submit" wire:loading.attr="disabled" data-confirm>{{ $dialog['confirm'] }}</x-button>
                </div>
            </form>
        </div>
    @endif
</div>
