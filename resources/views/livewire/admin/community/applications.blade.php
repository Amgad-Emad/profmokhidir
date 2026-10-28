@use('App\Constants\CommunityStatus')
@use('App\Constants\DateFormats')
@use('App\Constants\StatusTone')
@use('App\Livewire\Admin\Community\Applications')
<div>
    <x-admin.page-header :eyebrow="__('Community')" :title="__('Applications and members')" :intro="__('Applications are approved or rejected here; members can be suspended, reinstated or removed. Every decision is emailed to the person and recorded in the audit log.')" />

    <div class="mt-8"><x-admin.flash /></div>

    <x-admin.list-toolbar :toolbar="$toolbar" :items="__('applicants and members')" :placeholder="__('Search names and emails')" />

    <x-admin.list-results :density="$toolbar['density']">
    @if ($users->isEmpty())
        @if ($filtered)
            <x-admin.list-empty :title="__('No applications yet')" text="" :filtered="true" />
        @else
            <x-empty-state :message="match ($current) {
                CommunityStatus::Pending => __('No applications are waiting for review.'),
                CommunityStatus::Approved => __('There are no members yet.'),
                CommunityStatus::Suspended => __('No members are suspended.'),
                CommunityStatus::Rejected => __('No applications have been rejected.'),
                default => __('Nobody has applied yet.'),
            }" />
        @endif
    @else
        <ul class="list-cards" data-applications>
            @foreach ($users as $user)
                <li wire:key="user-{{ $user->getKey() }}" class="list-card rounded-card border border-rule bg-surface-panel p-5" data-user="{{ $user->getKey() }}">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                                <p class="font-medium text-ink" dir="auto">{{ $user->name }}</p>
                                <p class="font-mono text-sm text-ink-muted" dir="ltr">{{ $user->email }}</p>
                                @if ($user->email_verified_at === null)
                                    <x-admin.status-badge :tone="StatusTone::Bronze" :label="__('Email unverified')" />
                                @endif
                            </div>
                            <dl class="mt-4 grid gap-x-8 gap-y-3 text-sm sm:grid-cols-2">
                                <div>
                                    <dt class="t-label text-accent-text">{{ __('Affiliation') }}</dt>
                                    <dd class="mt-1 font-normal text-ink" dir="auto">{{ $user->affiliation ?: __('Not given') }}</dd>
                                </div>
                                <div>
                                    <dt class="t-label text-accent-text">{{ __('Academic field') }}</dt>
                                    <dd class="mt-1 font-normal text-ink" dir="auto">{{ $user->academic_field ?: __('Not given') }}</dd>
                                </div>
                                <div class="sm:col-span-2">
                                    <dt class="t-label text-accent-text">{{ __('Reason for joining') }}</dt>
                                    <dd class="mt-1 whitespace-pre-line font-normal text-ink" dir="auto">{{ $user->reason_for_joining ?: __('Not given') }}</dd>
                                </div>
                                <div>
                                    <dt class="t-label text-accent-text">{{ __('Applied') }}</dt>
                                    <dd class="mt-1 font-normal text-ink"><x-date :date="$user->created_at" /></dd>
                                </div>
                                @if ($user->reviewed_at)
                                    <div>
                                        <dt class="t-label text-accent-text">{{ __('Reviewed') }}</dt>
                                        <dd class="mt-1 font-normal text-ink"><x-date :date="$user->reviewed_at" />@if ($user->reviewer) {{ __('by :name', ['name' => $user->reviewer->name]) }}@endif</dd>
                                    </div>
                                @endif
                                @if ($user->review_note)
                                    <div class="sm:col-span-2">
                                        <dt class="t-label text-accent-text">{{ __('Note') }}</dt>
                                        <dd class="mt-1 whitespace-pre-line font-normal text-ink" dir="auto">{{ $user->review_note }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </div>
                        <div class="flex shrink-0 flex-wrap items-center gap-x-4 gap-y-2 text-sm lg:flex-col lg:items-end">
                            <x-admin.status-badge :tone="StatusTone::forCommunity($user->community_status)" :label="$user->community_status->label()" />
                            @if ($user->community_status === CommunityStatus::Pending || $user->community_status === CommunityStatus::Rejected)
                                <x-button variant="quiet" size="sm" type="button" wire:click="decide('{{ Applications::ACTION_APPROVE }}', {{ $user->getKey() }})" data-approve>{{ __('Approve') }}<span class="sr-only"> {{ $user->name }}</span></x-button>
                            @endif
                            @if ($user->community_status === CommunityStatus::Pending)
                                <x-button variant="danger" size="sm" type="button" wire:click="decide('{{ Applications::ACTION_REJECT }}', {{ $user->getKey() }})" data-reject>{{ __('Reject') }}<span class="sr-only"> {{ $user->name }}</span></x-button>
                            @endif
                            @if ($user->community_status === CommunityStatus::Approved)
                                <x-button variant="danger" size="sm" type="button" wire:click="decide('{{ Applications::ACTION_SUSPEND }}', {{ $user->getKey() }})" data-suspend>{{ __('Suspend') }}<span class="sr-only"> {{ $user->name }}</span></x-button>
                            @endif
                            @if ($user->community_status === CommunityStatus::Suspended)
                                <x-button variant="quiet" size="sm" type="button" wire:click="decide('{{ Applications::ACTION_REINSTATE }}', {{ $user->getKey() }})" data-reinstate>{{ __('Reinstate') }}<span class="sr-only"> {{ $user->name }}</span></x-button>
                            @endif
                            @if (in_array($user->community_status, [CommunityStatus::Approved, CommunityStatus::Suspended], true))
                                <x-button variant="danger" size="sm" type="button" wire:click="decide('{{ Applications::ACTION_REMOVE }}', {{ $user->getKey() }})" data-remove>{{ __('Remove') }}<span class="sr-only"> {{ $user->name }}</span></x-button>
                            @endif
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
        {{ $users->links('components.admin.pagination') }}
    @endif
    </x-admin.list-results>

    @if ($deciding !== null && $dialog !== null)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-surface-overlay p-4" x-data x-trap.noscroll.inert="true" x-on:keydown.escape.window="$wire.cancelDecision()" data-decision-dialog="{{ $decidingAction }}">
            <form novalidate wire:submit="confirmDecision" role="{{ $dialog['danger'] ? 'alertdialog' : 'dialog' }}" aria-modal="true" aria-labelledby="decision-title" aria-describedby="decision-description" @class(['confirm-dialog max-w-lg', 'confirm-dialog--danger' => $dialog['danger']]) >
                <div class="confirm-dialog__head">
                    @if ($dialog['danger'])
                        <span class="confirm-dialog__icon" data-danger-icon><x-icon name="warning" class="size-6" /></span>
                    @endif
                    <h2 id="decision-title" class="confirm-dialog__title">{{ $dialog['title'] }}</h2>
                </div>
                <p id="decision-description" class="confirm-dialog__body">{{ $deciding->name }} · <span dir="ltr">{{ $deciding->email }}</span>. {{ $dialog['body'] }}</p>
                <div class="mt-6">
                    <x-form.textarea model="note" :label="__('Note to the applicant (optional)')" rows="4" :hint="__('Included in the email and kept in the audit log.')" />
                </div>
                <div class="confirm-dialog__actions">
                    <x-button type="button" variant="quiet" wire:click="cancelDecision" x-init="$nextTick(() => $el.focus())" data-cancel>{{ __('Cancel') }}</x-button>
                    <x-button type="submit" :variant="$dialog['danger'] ? 'danger-filled' : 'primary'" wire:loading.attr="disabled" data-confirm>{{ $dialog['confirm'] }}</x-button>
                </div>
            </form>
        </div>
    @endif
</div>
