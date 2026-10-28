@use('App\Constants\CommunityStatus')
@use('App\Constants\Roles')
@use('App\Constants\StatusTone')
@use('App\Livewire\Admin\Users\Index')
<div>
    <x-admin.page-header :eyebrow="__('Settings')" :title="__('Users & roles')" :intro="__('Staff accounts and community users. Invite administrators and editors by email; they choose their own password from the link and administrators set up two-factor authentication on first sign-in.')">
        <x-button type="button" wire:click="openInvite" data-invite>{{ __('Invite a staff member') }}</x-button>
    </x-admin.page-header>

    <div class="mt-8">
        <x-admin.flash />
        @error('action')
            <x-alert tone="danger" role="alert" class="mb-6" data-action-error>{{ $message }}</x-alert>
        @enderror
    </div>

    <p class="text-sm font-normal text-ink-muted" data-active-admins>{!! __('Active administrators: :count. The last one can never be demoted or deactivated.', ['count' => '<span class="font-mono text-ink">'.e($activeAdmins).'</span>']) !!}</p>

    <x-admin.list-toolbar :toolbar="$toolbar" :items="__('users')" :placeholder="__('Search names and emails')" />

    <x-admin.list-results :density="$toolbar['density']">
    @if ($users->isEmpty())
        @if ($toolbar['filtered'])
            <x-admin.list-empty :title="__('No accounts yet')" text="" :filtered="true" />
        @else
            <x-empty-state :message="__('There are no accounts yet. Invite the first staff member.')" />
        @endif
    @else
        <div class="admin-table-wrap">
            <table class="admin-table w-full text-start text-sm" data-users>
                <caption class="sr-only">{{ __('Users') }}</caption>
                <thead>
                    <tr>
                        <th scope="col">{{ __('Name') }}</th>
                        <th scope="col">{{ __('Roles') }}</th>
                        <th scope="col">{{ __('Community') }}</th>
                        <th scope="col">{{ __('Account') }}</th>
                        <th scope="col" class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        @php
                            $row = $rows[$user->getKey()];
                            $isSelf = auth()->id() === $user->getKey();
                            $isStaff = $row['staffRole'] !== null;
                        @endphp
                        <tr wire:key="user-{{ $user->getKey() }}" data-user="{{ $user->getKey() }}">
                            <td class="font-medium text-ink">
                                {{ $user->name }}@if ($isSelf) <span class="ms-1 font-normal text-ink-muted">{{ __('(you)') }}</span>@endif
                                <span class="mt-1 block font-mono text-sm font-medium text-ink-muted" dir="ltr">{{ $user->email }}</span>
                                @if (\App\Constants\Locales::isValid($user->locale))
                                    <span class="mt-1 block text-sm font-normal text-ink-muted" data-user-locale="{{ $user->locale }}"><span class="sr-only">{{ __('Language') }}: </span><span lang="{{ \App\Constants\Locales::html($user->locale) }}">{{ \App\Constants\Locales::native($user->locale) }}</span></span>
                                @endif
                            </td>
                            <td>
                                @if ($row['roles'] === [])
                                    <span class="font-normal text-ink-muted">{{ __('None') }}</span>
                                @else
                                    <span class="flex flex-wrap gap-1.5">
                                        @foreach ($row['roles'] as $roleName)
                                            <x-admin.status-badge :tone="in_array($roleName, Roles::staff(), true) ? StatusTone::Cypress : StatusTone::Ash" :label="Roles::labels()[$roleName] ?? $roleName" />
                                        @endforeach
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if ($user->community_status === CommunityStatus::None)
                                    <span class="font-normal text-ink-muted">{{ $user->community_status->label() }}</span>
                                @else
                                    <x-admin.status-badge :tone="StatusTone::forCommunity($user->community_status)" :label="$user->community_status->label()" />
                                @endif
                            </td>
                            <td>
                                @if (! $user->isActive())
                                    <x-admin.status-badge :tone="StatusTone::Ash" :label="__('Deactivated')" />
                                @elseif ($row['pendingInvite'])
                                    <x-admin.status-badge :tone="StatusTone::Bronze" :label="__('Invitation pending')" />
                                @elseif ($user->email_verified_at === null)
                                    <x-admin.status-badge :tone="StatusTone::Bronze" :label="__('Email unverified')" />
                                @else
                                    <x-admin.status-badge :tone="StatusTone::Cypress" :label="__('Active')" />
                                @endif
                            </td>
                            <td class="text-end">
                                @unless ($isSelf)
                                    <div class="flex flex-wrap items-center justify-end gap-x-4 gap-y-2">
                                        <x-button variant="quiet" size="sm" type="button" wire:click="editRole({{ $user->getKey() }})" data-change-role>{{ __('Change role') }}<span class="sr-only"> {{ __('of :name', ['name' => $user->name]) }}</span></x-button>
                                        @if ($row['pendingInvite'] && $user->isActive())
                                            <x-button variant="quiet" size="sm" type="button" wire:click="confirm('{{ Index::ACTION_RESEND }}', {{ $user->getKey() }})" data-resend>{{ __('Resend invite') }}<span class="sr-only"> {{ __('to :name', ['name' => $user->name]) }}</span></x-button>
                                        @endif
                                        @if ($isStaff || ! $user->isActive())
                                            @if ($user->isActive())
                                                <x-button variant="danger" size="sm" type="button" wire:click="confirm('{{ Index::ACTION_DEACTIVATE }}', {{ $user->getKey() }})" data-deactivate>{{ __('Deactivate') }}<span class="sr-only"> {{ $user->name }}</span></x-button>
                                            @else
                                                <x-button variant="quiet" size="sm" type="button" wire:click="confirm('{{ Index::ACTION_REACTIVATE }}', {{ $user->getKey() }})" data-reactivate>{{ __('Reactivate') }}<span class="sr-only"> {{ $user->name }}</span></x-button>
                                            @endif
                                        @endif
                                    </div>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $users->links('components.admin.pagination') }}
    @endif
    </x-admin.list-results>

    {{-- Invite dialog --}}
    @if ($inviting)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-surface-overlay p-4" x-data x-trap.noscroll.inert="true" x-on:keydown.escape.window="$wire.cancelInvite()" data-invite-dialog>
            <form wire:submit="sendInvite" role="dialog" aria-modal="true" aria-labelledby="invite-title" aria-describedby="invite-description" class="w-full max-w-lg rounded-card border border-rule bg-surface-panel p-6 sm:p-8" novalidate>
                <h2 id="invite-title" class="font-display text-3xl font-light leading-tight">{{ __('Invite a staff member') }}</h2>
                <p id="invite-description" class="mt-3 text-base font-normal text-ink">{{ __('They receive an email with a link to choose their password. No password is shown here or sent by email.') }}</p>
                <div class="form-grid mt-6">
                    <x-form.input model="invite.name" :label="__('Name')" required />
                    <x-form.input model="invite.email" :label="__('Email')" type="email" required />
                    <x-form.select model="invite.role" :label="__('Role')" :options="array_intersect_key(Roles::labels(), array_flip(Roles::staff()))" required :hint="__('Administrators manage users, settings and the community and must use two-factor authentication. Editors manage content and tributes.')" />
                    <x-form.select model="invite.locale" :label="__('Language of the invitation')" :options="$localeOptions" :optional="false" :hint="__('The email and the dashboard start in this language; they can change it later.')" data-invite-locale />
                </div>
                <div class="mt-8 flex flex-wrap justify-end gap-3">
                    <x-button type="button" variant="quiet" wire:click="cancelInvite">{{ __('Cancel') }}</x-button>
                    <x-button type="submit" wire:loading.attr="disabled" data-send-invite>{{ __('Send the invitation') }}</x-button>
                </div>
            </form>
        </div>
    @endif

    {{-- Role dialog --}}
    @if ($editing !== null)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-surface-overlay p-4" x-data x-trap.noscroll.inert="true" x-on:keydown.escape.window="$wire.cancelRole()" data-role-dialog>
            <form wire:submit="saveRole" role="dialog" aria-modal="true" aria-labelledby="role-title" aria-describedby="role-description" class="w-full max-w-lg rounded-card border border-rule bg-surface-panel p-6 sm:p-8" novalidate>
                <h2 id="role-title" class="font-display text-3xl font-light leading-tight">{{ __('Change the role of :name', ['name' => $editing->name]) }}</h2>
                <p id="role-description" class="mt-3 text-base font-normal text-ink"><span dir="ltr">{{ $editing->email }}</span>. {{ __('Any community membership is kept. A new administrator must set up two-factor authentication before the dashboard opens.') }}</p>
                <div class="mt-6">
                    <x-form.select model="newRole" :label="__('Dashboard role')" :options="$staffRoleOptions" required />
                </div>
                <div class="mt-8 flex flex-wrap justify-end gap-3">
                    <x-button type="button" variant="quiet" wire:click="cancelRole">{{ __('Cancel') }}</x-button>
                    <x-button type="submit" wire:loading.attr="disabled" data-save-role>{{ __('Save role') }}</x-button>
                </div>
            </form>
        </div>
    @endif

    @if ($confirming !== null && $dialog !== null)
        <x-admin.confirm-dialog :show="true" :danger="$dialog['danger']" :icon="null" :title="$dialog['title']" action="runConfirmed" :confirm-label="$dialog['confirm']">
            {{ $confirming->name }} · <span dir="ltr">{{ $confirming->email }}</span>. {{ $dialog['body'] }}
        </x-admin.confirm-dialog>
    @endif
</div>
