@use('App\Constants\Roles')
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

    <x-admin.list-toolbar :toolbar="$toolbar" :items="__('users')" :placeholder="__('Search names and emails')" :table="$table" />

    <x-admin.list-results :density="$toolbar['density']">
        <x-admin.data-table :table="$table" data-users>
            <x-slot:empty>
                @if ($toolbar['filtered'])
                    <x-admin.list-empty :title="__('No accounts yet')" text="" :filtered="true" />
                @else
                    <x-empty-state :message="__('There are no accounts yet. Invite the first staff member.')" />
                @endif
            </x-slot:empty>
        </x-admin.data-table>
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
