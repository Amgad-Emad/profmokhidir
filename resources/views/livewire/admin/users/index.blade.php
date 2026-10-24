@use('App\Constants\CommunityStatus')
@use('App\Constants\Roles')
@use('App\Constants\StatusTone')
@use('App\Livewire\Admin\Users\Index')
<div>
    <x-admin.page-header eyebrow="Settings" title="Users & roles" intro="Staff accounts and community users. Invite administrators and editors by email; they choose their own password from the link and administrators set up two-factor authentication on first sign-in.">
        <x-button type="button" wire:click="openInvite" data-invite>Invite a staff member</x-button>
    </x-admin.page-header>

    <div class="mt-8">
        <x-admin.flash />
        @error('action')
            <x-alert tone="bronze" role="alert" class="mb-6" data-action-error>{{ $message }}</x-alert>
        @enderror
    </div>

    <p class="text-sm font-light text-ink-muted" data-active-admins>Active administrators: <span class="font-mono text-ink">{{ $activeAdmins }}</span>. The last one can never be demoted or deactivated.</p>

    <form novalidate class="mt-6 grid gap-4 md:grid-cols-4" wire:submit.prevent role="search" aria-label="Search users">
        <x-form.input type="search" model="search" id="search" live label="Search" :optional="false" loading="search" class="md:col-span-2" placeholder="Name or email" autocomplete="off" />
        <x-form.select model="role" id="filter-role" live label="Role" :optional="false" placeholder="All roles" :options="$roleOptions" data-filter="role" />
        <x-form.select model="status" id="filter-status" live label="Community status" :optional="false" placeholder="Any" :options="$statusOptions" data-filter="status" />
    </form>

    @if ($users->isEmpty())
        <x-empty-state :message="trim($search) !== '' || $role !== '' || $status !== '' ? 'Nobody matches your search.' : 'There are no accounts yet. Invite the first staff member.'" />
        @if (trim($search) !== '' || $role !== '' || $status !== '')
            <p class="mt-4 text-sm"><x-button variant="quiet" size="sm" type="button" wire:click="clearFilters">Clear the search and filters</x-button></p>
        @endif
    @else
        <div class="relative mt-6 overflow-x-auto rounded-card border border-rule bg-surface-panel">
            <table class="admin-table w-full text-left text-sm" data-users>
                <caption class="sr-only">Users</caption>
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Roles</th>
                        <th scope="col">Community</th>
                        <th scope="col">Account</th>
                        <th scope="col" class="text-right">Actions</th>
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
                                {{ $user->name }}@if ($isSelf) <span class="ml-1 font-light text-ink-muted">(you)</span>@endif
                                <span class="mt-1 block font-mono text-xs font-normal text-ink-muted">{{ $user->email }}</span>
                            </td>
                            <td>
                                @if ($row['roles'] === [])
                                    <span class="font-light text-ink-muted">None</span>
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
                                    <span class="font-light text-ink-muted">{{ $user->community_status->label() }}</span>
                                @else
                                    <x-admin.status-badge :tone="StatusTone::forCommunity($user->community_status)" :label="$user->community_status->label()" />
                                @endif
                            </td>
                            <td>
                                @if (! $user->isActive())
                                    <x-admin.status-badge :tone="StatusTone::Ash" label="Deactivated" />
                                @elseif ($row['pendingInvite'])
                                    <x-admin.status-badge :tone="StatusTone::Bronze" label="Invitation pending" />
                                @elseif ($user->email_verified_at === null)
                                    <x-admin.status-badge :tone="StatusTone::Bronze" label="Email unverified" />
                                @else
                                    <x-admin.status-badge :tone="StatusTone::Cypress" label="Active" />
                                @endif
                            </td>
                            <td class="text-right">
                                @unless ($isSelf)
                                    <div class="flex flex-wrap items-center justify-end gap-x-4 gap-y-2">
                                        <x-button variant="quiet" size="sm" type="button" wire:click="editRole({{ $user->getKey() }})" data-change-role>Change role<span class="sr-only"> of {{ $user->name }}</span></x-button>
                                        @if ($row['pendingInvite'] && $user->isActive())
                                            <x-button variant="quiet" size="sm" type="button" wire:click="confirm('{{ Index::ACTION_RESEND }}', {{ $user->getKey() }})" data-resend>Resend invite<span class="sr-only"> to {{ $user->name }}</span></x-button>
                                        @endif
                                        @if ($isStaff || ! $user->isActive())
                                            @if ($user->isActive())
                                                <x-button variant="quiet" size="sm" danger type="button" wire:click="confirm('{{ Index::ACTION_DEACTIVATE }}', {{ $user->getKey() }})" data-deactivate>Deactivate<span class="sr-only"> {{ $user->name }}</span></x-button>
                                            @else
                                                <x-button variant="quiet" size="sm" type="button" wire:click="confirm('{{ Index::ACTION_REACTIVATE }}', {{ $user->getKey() }})" data-reactivate>Reactivate<span class="sr-only"> {{ $user->name }}</span></x-button>
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

    {{-- Invite dialog --}}
    @if ($inviting)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-surface-overlay p-4" x-data x-trap.noscroll.inert="true" x-on:keydown.escape.window="$wire.cancelInvite()" data-invite-dialog>
            <form wire:submit="sendInvite" role="dialog" aria-modal="true" aria-labelledby="invite-title" aria-describedby="invite-description" class="w-full max-w-lg rounded-card border border-rule bg-surface-panel p-6 sm:p-8" novalidate>
                <h2 id="invite-title" class="font-display text-3xl font-light leading-tight">Invite a staff member</h2>
                <p id="invite-description" class="mt-3 text-base font-light text-ink">They receive an email with a link to choose their password. No password is shown here or sent by email.</p>
                <div class="mt-6 space-y-5">
                    <x-form.input model="invite.name" label="Name" required />
                    <x-form.input model="invite.email" label="Email" type="email" required />
                    <x-form.select model="invite.role" label="Role" :options="array_intersect_key(Roles::labels(), array_flip(Roles::staff()))" required hint="Administrators manage users, settings and the community and must use two-factor authentication. Editors manage content and tributes." />
                </div>
                <div class="mt-8 flex flex-wrap justify-end gap-3">
                    <x-button type="button" variant="quiet" wire:click="cancelInvite">Cancel</x-button>
                    <x-button type="submit" wire:loading.attr="disabled" data-send-invite>Send the invitation</x-button>
                </div>
            </form>
        </div>
    @endif

    {{-- Role dialog --}}
    @if ($editing !== null)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-surface-overlay p-4" x-data x-trap.noscroll.inert="true" x-on:keydown.escape.window="$wire.cancelRole()" data-role-dialog>
            <form wire:submit="saveRole" role="dialog" aria-modal="true" aria-labelledby="role-title" aria-describedby="role-description" class="w-full max-w-lg rounded-card border border-rule bg-surface-panel p-6 sm:p-8" novalidate>
                <h2 id="role-title" class="font-display text-3xl font-light leading-tight">Change the role of {{ $editing->name }}</h2>
                <p id="role-description" class="mt-3 text-base font-light text-ink">{{ $editing->email }}. Any community membership is kept. A new administrator must set up two-factor authentication before the dashboard opens.</p>
                <div class="mt-6">
                    <x-form.select model="newRole" label="Dashboard role" :options="$staffRoleOptions" required />
                </div>
                <div class="mt-8 flex flex-wrap justify-end gap-3">
                    <x-button type="button" variant="quiet" wire:click="cancelRole">Cancel</x-button>
                    <x-button type="submit" wire:loading.attr="disabled" data-save-role>Save role</x-button>
                </div>
            </form>
        </div>
    @endif

    @if ($confirming !== null && $dialog !== null)
        <x-admin.confirm-dialog :show="true" :title="$dialog['title']" action="runConfirmed" :confirm-label="$dialog['confirm']">
            {{ $confirming->name }} · {{ $confirming->email }}. {{ $dialog['body'] }}
        </x-admin.confirm-dialog>
    @endif
</div>
