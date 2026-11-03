<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Users;

use App\Constants\AccountState;
use App\Constants\AdminNavigation;
use App\Constants\CommunityStatus;
use App\Constants\DataTable;
use App\Constants\ListToolbar;
use App\Constants\Locales;
use App\Constants\Roles;
use App\Constants\StatusTone;
use App\Exceptions\UserManagementException;
use App\Http\Requests\Admin\StaffInviteRequest;
use App\Http\Requests\Admin\StaffRoleRequest;
use App\Livewire\Admin\Concerns\NotifiesEditors;
use App\Livewire\Admin\Concerns\WithDataTable;
use App\Livewire\Admin\Concerns\WithListToolbar;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\DataTableService;
use App\Services\RoleService;
use App\Services\UserService;
use App\Support\DataTable\Cell;
use App\Support\DataTable\Column;
use App\Support\DataTable\Row;
use App\Support\DataTable\RowAction;
use App\Support\ListToolbar\ListFilter;
use App\Support\ListToolbar\ListSort;
use App\Support\ListToolbar\ListView;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Users & roles (admin only): every staff and community account under the
 * list toolbar (views, search; Role, Status, Community and Two-factor filters), staff invitations by set-password email, role changes,
 * deactivation and reactivation. Every change goes through UserService, which
 * audits it and protects the last active administrator.
 */
class Index extends Component
{
    use AuthorizesRequests, NotifiesEditors, WithDataTable, WithListToolbar, WithPagination;

    public const int PER_PAGE = DataTable::DEFAULT_PAGE_SIZE;

    public const string ACTION_DEACTIVATE = 'deactivate';

    public const string ACTION_REACTIVATE = 'reactivate';

    public const string ACTION_RESEND = 'resend';

    public const string VIEW_STAFF = 'staff';

    public const string VIEW_MEMBERS = 'members';

    public const string VIEW_INVITED = 'invited';

    public const string VIEW_DEACTIVATED = 'deactivated';

    public const string VIEW_NO_TWO_FACTOR = 'no-two-factor';

    public bool $inviting = false;

    /** @var array{name: string, email: string, role: string, locale: string} */
    public array $invite = ['name' => '', 'email' => '', 'role' => Roles::EDITOR, 'locale' => Locales::DEFAULT];

    public ?int $roleEditingId = null;

    public string $newRole = '';

    public ?int $confirmingId = null;

    public ?string $confirmingAction = null;

    public function mount(): void
    {
        $this->authorize('invite', User::class);
    }

    protected function listScreen(): string
    {
        return AdminNavigation::USERS;
    }

    protected function listFilters(): array
    {
        return [
            ListFilter::multi(UserRepositoryInterface::FILTER_ROLE, __('Role'), Roles::labels()),
            ListFilter::multi(UserRepositoryInterface::FILTER_ACCOUNT, __('Status'), AccountState::labels()),
            ListFilter::multi(UserRepositoryInterface::FILTER_COMMUNITY, __('Community'), CommunityStatus::labels()),
            ListFilter::single(UserRepositoryInterface::FILTER_TWO_FACTOR, __('Two-factor'), [UserRepositoryInterface::YES => __('Two-factor on'), UserRepositoryInterface::NO => __('Two-factor off')]),
        ];
    }

    protected function listViews(): array
    {
        return [
            new ListView(ListToolbar::VIEW_ALL, __('All')),
            new ListView(self::VIEW_STAFF, __('Staff'), [UserRepositoryInterface::FILTER_ROLE => implode(ListToolbar::VALUE_SEPARATOR, Roles::staff())]),
            new ListView(self::VIEW_MEMBERS, __('Members'), [UserRepositoryInterface::FILTER_COMMUNITY => CommunityStatus::Approved->value]),
            new ListView(self::VIEW_INVITED, __('Invitations pending'), [UserRepositoryInterface::FILTER_ACCOUNT => AccountState::Invited->value]),
            new ListView(self::VIEW_NO_TWO_FACTOR, __('Staff without two-factor'), [UserRepositoryInterface::FILTER_ROLE => implode(ListToolbar::VALUE_SEPARATOR, Roles::staff()), UserRepositoryInterface::FILTER_TWO_FACTOR => UserRepositoryInterface::NO]),
            new ListView(self::VIEW_DEACTIVATED, __('Deactivated'), [UserRepositoryInterface::FILTER_ACCOUNT => AccountState::Deactivated->value]),
        ];
    }

    protected function listSorts(): array
    {
        return [
            new ListSort('', ListToolbar::SORT_ASC, __('Name, A to Z')),
            new ListSort('name', ListToolbar::SORT_DESC, __('Name, Z to A')),
            new ListSort('created_at', ListToolbar::SORT_DESC, __('Newest accounts first')),
        ];
    }

    protected function listCount(array $criteria, string $search): int
    {
        return $this->users()->countFiltered($search, $criteria);
    }

    /**
     * @return list<string>
     */
    public static function actions(): array
    {
        return [self::ACTION_DEACTIVATE, self::ACTION_REACTIVATE, self::ACTION_RESEND];
    }

    // Invitations -----------------------------------------------------------

    public function openInvite(): void
    {
        $this->authorize('invite', User::class);
        $this->invite = ['name' => '', 'email' => '', 'role' => Roles::EDITOR, 'locale' => auth()->user()?->preferredLocale() ?? app()->getLocale()];
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
        $this->toast(__(':name has been invited as :role. They have been emailed a link to set their password.', ['name' => $user->name, 'role' => mb_strtolower(Roles::labels()[$validated['role']])]));
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
        $this->toast($role === null
            ? __(':name is now without dashboard access.', ['name' => $user->name])
            : __(':name is now :role.', ['name' => $user->name, 'role' => mb_strtolower(Roles::labels()[$role])]));
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
                self::ACTION_DEACTIVATE => __(':name\'s account has been deactivated. They can no longer sign in.', ['name' => $this->users()->deactivate($user, $actor)->name]),
                self::ACTION_REACTIVATE => __(':name\'s account has been reactivated.', ['name' => $this->users()->reactivate($user, $actor)->name]),
                self::ACTION_RESEND => __(':name has been sent a new invitation link.', ['name' => $this->users()->resendInvitation($user, $actor)->name]),
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
        $users = $this->users()->filter($this->search, $this->listCriteria(), $this->perPage, $this->sort ?: null, $this->direction);

        return view('livewire.admin.users.index', [
            'users' => $users,
            'table' => $this->dataTable($users, $users->getCollection()->map(fn (User $user): Row => $this->row($user))->all(), [
                'caption' => __('Users'),
                'id' => 'users-table',
            ]),
            'toolbar' => $this->listToolbar($users->total()),
            'staffRoleOptions' => StaffRoleRequest::labels(),
            'localeOptions' => array_combine(Locales::all(), array_map(Locales::native(...), Locales::all())),
            'activeAdmins' => $this->users()->countActiveAdmins(),
            'editing' => $this->roleEditingId === null ? null : $this->users()->find($this->roleEditingId),
            'confirming' => $this->confirmingId === null ? null : $this->users()->find($this->confirmingId),
            'dialog' => $this->dialogCopy(),
        ])->layout('components.layouts.admin', ['title' => AdminNavigation::get(AdminNavigation::USERS)['label']]);
    }

    /**
     * Name with email and language, roles, community membership and account
     * state; when the account was made, on request.
     */
    protected function dataTableColumns(): array
    {
        return [
            Column::primary('name', __('Name'), sortable: true),
            Column::badges('roles', __('Roles'), width: '10rem', priority: DataTable::PRIORITY_TABLET),
            Column::badges('community', __('Community'), width: '9rem', priority: DataTable::PRIORITY_DESKTOP),
            Column::status('account', __('Account')),
            Column::relative('created_at', __('Joined'), visible: false),
        ];
    }

    /**
     * One account as a table row: Change role and Deactivate at hand, Resend
     * invite and Reactivate in the menu; nothing for the signed-in user's own row.
     */
    private function row(User $user): Row
    {
        $id = (int) $user->getKey();
        $isSelf = auth()->id() === $id;
        $roles = $this->roles()->rolesOf($user);
        $isStaff = $this->users()->staffRoleOf($user) !== null;
        $pendingInvite = $this->users()->hasPendingInvitation($user);
        $account = match (true) {
            ! $user->isActive() => [__('Deactivated'), StatusTone::Ash],
            $pendingInvite => [__('Invitation pending'), StatusTone::Bronze],
            $user->email_verified_at === null => [__('Email unverified'), StatusTone::Bronze],
            default => [__('Active'), StatusTone::Cypress],
        };
        $actor = auth()->user();
        $can = fn (string $ability): bool => ! $isSelf && ($actor?->can($ability, $user) ?? false);

        return new Row(
            id: $id,
            title: $user->name,
            supporting: [$user->email],
            badges: array_values(array_filter([
                $isSelf ? ['label' => __('(you)'), 'tone' => StatusTone::Ash] : null,
                // The language the account works in, named in that language.
                Locales::isValid($user->locale) ? ['label' => Locales::native($user->locale), 'tone' => StatusTone::Ash, 'lang' => Locales::html($user->locale), 'title' => __('Language'), 'data' => ['data-user-locale' => $user->locale]] : null,
            ])),
            cells: [
                'roles' => Cell::badges(array_map(fn (string $role): array => ['label' => Roles::labels()[$role] ?? $role, 'tone' => in_array($role, Roles::staff(), true) ? StatusTone::Cypress : StatusTone::Ash], $roles), __('None')),
                'community' => $user->community_status === CommunityStatus::None
                    ? Cell::text($user->community_status->label(), muted: true)
                    : Cell::badges([['label' => $user->community_status->label(), 'tone' => StatusTone::forCommunity($user->community_status)]]),
                'account' => Cell::status($account[0], $account[1]),
                'created_at' => app(DataTableService::class)->relative($user->created_at),
            ],
            actions: array_values(array_filter([
                $can('assignRole') ? RowAction::wire(__('Change role'), 'users', 'editRole', [$id], inline: true, hook: 'data-change-role') : null,
                $pendingInvite && $user->isActive() && $can($this->ability(self::ACTION_RESEND)) ? RowAction::wire(__('Resend invite'), 'send', 'confirm', [self::ACTION_RESEND, $id], hook: 'data-resend') : null,
                $isStaff && $user->isActive() && $can($this->ability(self::ACTION_DEACTIVATE)) ? RowAction::wire(__('Deactivate'), 'lock', 'confirm', [self::ACTION_DEACTIVATE, $id], inline: true, danger: true, hook: 'data-deactivate') : null,
                ! $user->isActive() && $can($this->ability(self::ACTION_REACTIVATE)) ? RowAction::wire(__('Reactivate'), 'undo', 'confirm', [self::ACTION_REACTIVATE, $id], inline: true, hook: 'data-reactivate') : null,
            ])),
            attributes: ['data-user' => $id],
        );
    }

    /**
     * @return array{title: string, body: string, confirm: string}|null
     */
    public function dialogCopy(): ?array
    {
        return match ($this->confirmingAction) {
            self::ACTION_DEACTIVATE => ['title' => __('Deactivate this account?'), 'body' => __('They will be signed out and cannot sign in again until the account is reactivated. Nothing they created is removed.'), 'confirm' => __('Deactivate'), 'danger' => true],
            self::ACTION_REACTIVATE => ['title' => __('Reactivate this account?'), 'body' => __('They can sign in again with their existing password and role.'), 'confirm' => __('Reactivate'), 'danger' => false],
            self::ACTION_RESEND => ['title' => __('Resend the invitation?'), 'body' => __('A new set-password link will be emailed. Any earlier link stops working.'), 'confirm' => __('Send the link'), 'danger' => false],
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
