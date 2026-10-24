<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Users;

use App\Constants\AdminNavigation;
use App\Constants\CommunityStatus;
use App\Constants\Roles;
use App\Exceptions\UserManagementException;
use App\Http\Requests\Admin\StaffInviteRequest;
use App\Http\Requests\Admin\StaffRoleRequest;
use App\Livewire\Admin\Concerns\NotifiesEditors;
use App\Models\User;
use App\Services\RoleService;
use App\Services\UserService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Users & roles (admin only): every staff and community account with search
 * and filters, staff invitations by set-password email, role changes,
 * deactivation and reactivation. Every change goes through UserService, which
 * audits it and protects the last active administrator.
 */
class Index extends Component
{
    use AuthorizesRequests, NotifiesEditors, WithPagination;

    public const int PER_PAGE = 15;

    public const string ACTION_DEACTIVATE = 'deactivate';

    public const string ACTION_REACTIVATE = 'reactivate';

    public const string ACTION_RESEND = 'resend';

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $role = '';

    #[Url(except: '')]
    public string $status = '';

    public bool $inviting = false;

    /** @var array{name: string, email: string, role: string} */
    public array $invite = ['name' => '', 'email' => '', 'role' => Roles::EDITOR];

    public ?int $roleEditingId = null;

    public string $newRole = '';

    public ?int $confirmingId = null;

    public ?string $confirmingAction = null;

    public function mount(): void
    {
        $this->authorize('invite', User::class);

        if ($this->role !== '' && ! Roles::isValid($this->role)) {
            $this->role = '';
        }

        if ($this->status !== '' && ! in_array($this->status, CommunityStatus::values(), true)) {
            $this->status = '';
        }
    }

    /**
     * @return list<string>
     */
    public static function actions(): array
    {
        return [self::ACTION_DEACTIVATE, self::ACTION_REACTIVATE, self::ACTION_RESEND];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRole(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'role', 'status');
        $this->resetPage();
    }

    // Invitations -----------------------------------------------------------

    public function openInvite(): void
    {
        $this->authorize('invite', User::class);
        $this->invite = ['name' => '', 'email' => '', 'role' => Roles::EDITOR];
        $this->resetErrorBag();
        $this->inviting = true;
    }

    public function cancelInvite(): void
    {
        $this->inviting = false;
        $this->resetErrorBag();
    }

    public function sendInvite(): void
    {
        $this->authorize('invite', User::class);

        $request = new StaffInviteRequest;
        $validated = $this->validate(
            self::prefix($request->rules(), 'invite.'),
            self::prefix($request->messages(), 'invite.'),
        )['invite'];

        $user = $this->users()->invite($validated, auth()->user());

        $this->inviting = false;
        $this->toast($user->name.' has been invited as '.mb_strtolower(Roles::labels()[$validated['role']]).'. They have been emailed a link to set their password.');
    }

    // Roles -------------------------------------------------------------------

    public function editRole(int $id): void
    {
        $user = $this->users()->find($id);

        if ($user === null) {
            return;
        }

        $this->authorize('assignRole', $user);
        $this->roleEditingId = $id;
        $this->newRole = $this->users()->staffRoleOf($user) ?? StaffRoleRequest::NONE;
        $this->resetErrorBag();
    }

    public function cancelRole(): void
    {
        $this->roleEditingId = null;
        $this->newRole = '';
        $this->resetErrorBag();
    }

    public function saveRole(): void
    {
        $user = $this->roleEditingId === null ? null : $this->users()->find($this->roleEditingId);

        if ($user === null) {
            $this->cancelRole();

            return;
        }

        $this->authorize('assignRole', $user);

        $request = new StaffRoleRequest;
        $validated = Validator::make(['role' => $this->newRole], $request->rules(), $request->messages())->validate();
        $role = $validated['role'] === StaffRoleRequest::NONE ? null : $validated['role'];

        try {
            $this->users()->changeStaffRole($user, $role, auth()->user());
        } catch (UserManagementException $exception) {
            $this->addError('newRole', $exception->getMessage());

            return;
        }

        $this->cancelRole();
        $this->toast($user->name.' is now '.($role === null ? 'without dashboard access' : mb_strtolower(Roles::labels()[$role])).'.');
    }

    // Deactivate, reactivate, resend --------------------------------------------

    public function confirm(string $action, int $id): void
    {
        if (! in_array($action, self::actions(), true)) {
            return;
        }

        $user = $this->users()->find($id);

        if ($user === null) {
            return;
        }

        $this->authorize($this->ability($action), $user);
        $this->confirmingAction = $action;
        $this->confirmingId = $id;
        $this->resetErrorBag();
    }

    public function cancelConfirmation(): void
    {
        $this->confirmingAction = null;
        $this->confirmingId = null;
    }

    public function isConfirming(string $action): bool
    {
        return $this->confirmingAction === $action && $this->confirmingId !== null;
    }

    public function runConfirmed(): void
    {
        $user = $this->confirmingId === null ? null : $this->users()->find($this->confirmingId);
        $action = $this->confirmingAction;
        $this->cancelConfirmation();

        if ($user === null || $action === null) {
            return;
        }

        $this->authorize($this->ability($action), $user);
        $actor = auth()->user();

        try {
            $message = match ($action) {
                self::ACTION_DEACTIVATE => $this->users()->deactivate($user, $actor)->name.'\'s account has been deactivated. They can no longer sign in.',
                self::ACTION_REACTIVATE => $this->users()->reactivate($user, $actor)->name.'\'s account has been reactivated.',
                self::ACTION_RESEND => $this->users()->resendInvitation($user, $actor)->name.' has been sent a new invitation link.',
                default => throw new InvalidArgumentException('Unknown action.'),
            };
        } catch (UserManagementException $exception) {
            $this->addError('action', $exception->getMessage());

            return;
        }

        $this->toast($message);
    }

    public function render(): View
    {
        $users = $this->users()->search(
            $this->search,
            $this->role === '' ? null : $this->role,
            $this->status === '' ? null : CommunityStatus::from($this->status),
            self::PER_PAGE,
        );

        $rows = [];

        foreach ($users as $user) {
            $rows[(int) $user->getKey()] = [
                'roles' => $this->roles()->rolesOf($user),
                'staffRole' => $this->users()->staffRoleOf($user),
                'pendingInvite' => $this->users()->hasPendingInvitation($user),
            ];
        }

        return view('livewire.admin.users.index', [
            'users' => $users,
            'rows' => $rows,
            'roleOptions' => Roles::labels(),
            'statusOptions' => CommunityStatus::labels(),
            'staffRoleOptions' => StaffRoleRequest::labels(),
            'activeAdmins' => $this->users()->countActiveAdmins(),
            'editing' => $this->roleEditingId === null ? null : $this->users()->find($this->roleEditingId),
            'confirming' => $this->confirmingId === null ? null : $this->users()->find($this->confirmingId),
            'dialog' => $this->dialogCopy(),
        ])->layout('components.layouts.admin', ['title' => AdminNavigation::get(AdminNavigation::USERS)['label']]);
    }

    /**
     * @return array{title: string, body: string, confirm: string}|null
     */
    public function dialogCopy(): ?array
    {
        return match ($this->confirmingAction) {
            self::ACTION_DEACTIVATE => ['title' => 'Deactivate this account?', 'body' => 'They will be signed out and cannot sign in again until the account is reactivated. Nothing they created is removed.', 'confirm' => 'Deactivate', 'danger' => true],
            self::ACTION_REACTIVATE => ['title' => 'Reactivate this account?', 'body' => 'They can sign in again with their existing password and role.', 'confirm' => 'Reactivate', 'danger' => false],
            self::ACTION_RESEND => ['title' => 'Resend the invitation?', 'body' => 'A new set-password link will be emailed. Any earlier link stops working.', 'confirm' => 'Send the link', 'danger' => false],
            default => null,
        };
    }

    private function ability(string $action): string
    {
        return match ($action) {
            self::ACTION_DEACTIVATE => 'deactivate',
            self::ACTION_REACTIVATE => 'reactivate',
            self::ACTION_RESEND => 'resendInvitation',
            default => throw new InvalidArgumentException('Unknown action.'),
        };
    }

    /**
     * @param  array<string, mixed>  $items
     * @return array<string, mixed>
     */
    private static function prefix(array $items, string $prefix): array
    {
        $prefixed = [];

        foreach ($items as $key => $value) {
            $prefixed[$prefix.$key] = $value;
        }

        return $prefixed;
    }

    private function users(): UserService
    {
        return app(UserService::class);
    }

    private function roles(): RoleService
    {
        return app(RoleService::class);
    }
}
