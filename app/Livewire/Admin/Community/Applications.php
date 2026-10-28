<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Community;

use App\Constants\AdminNavigation;
use App\Constants\CommunityStatus;
use App\Constants\ListToolbar;
use App\Exceptions\CommunityApplicationException;
use App\Http\Requests\Admin\CommunityReviewRequest;
use App\Livewire\Admin\Concerns\NotifiesEditors;
use App\Livewire\Admin\Concerns\WithListToolbar;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\CommunityService;
use App\Support\ListToolbar\ListFilter;
use App\Support\ListToolbar\ListSort;
use App\Support\ListToolbar\ListView;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The community approval queue and member list under the list toolbar:
 * Pending, Members, Suspended, Rejected and All views; search; Status and
 * Email filters. Every decision takes an optional
 * note that is emailed to the person and kept in the audit log.
 */
class Applications extends Component
{
    use AuthorizesRequests, NotifiesEditors, WithListToolbar, WithPagination;

    public const string ACTION_APPROVE = 'approve';

    public const string ACTION_REJECT = 'reject';

    public const string ACTION_SUSPEND = 'suspend';

    public const string ACTION_REINSTATE = 'reinstate';

    public const string ACTION_REMOVE = 'remove';

    public const int PER_PAGE = 15;

    /** Opened as Members (the same screen, starting on the members). */
    #[Locked]
    public bool $membersPage = false;

    /** The user a decision is being confirmed for, and which decision. */
    public ?int $decidingId = null;

    public ?string $decidingAction = null;

    public string $note = '';

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
        $this->membersPage = request()->routeIs(AdminNavigation::routeFor(AdminNavigation::MEMBERS));
    }

    protected function listScreen(): string
    {
        return $this->membersPage ? AdminNavigation::MEMBERS : AdminNavigation::APPLICATIONS;
    }

    protected function listFilters(): array
    {
        $statuses = [];

        foreach (self::tabs() as $value) {
            $statuses[$value] = CommunityStatus::from($value)->label();
        }

        return [
            ListFilter::single(UserRepositoryInterface::FILTER_COMMUNITY, __('Status'), $statuses),
            ListFilter::single(UserRepositoryInterface::FILTER_EMAIL, __('Email'), [UserRepositoryInterface::YES => __('Email verified'), UserRepositoryInterface::NO => __('Email unverified')]),
        ];
    }

    /**
     * One view per status (Approved reads "Members"), then all applicants and members.
     */
    protected function listViews(): array
    {
        $views = [];

        foreach (self::tabs() as $value) {
            $status = CommunityStatus::from($value);
            $views[] = new ListView($value, $status === CommunityStatus::Approved ? __('Members') : $status->label(), [UserRepositoryInterface::FILTER_COMMUNITY => $value]);
        }

        return [...$views, new ListView(ListToolbar::VIEW_ALL, __('All'))];
    }

    protected function listDefaultView(): string
    {
        return $this->membersPage ? CommunityStatus::Approved->value : CommunityStatus::Pending->value;
    }

    protected function listSorts(): array
    {
        return [
            new ListSort('', ListToolbar::SORT_DESC, __('Newest first')),
            new ListSort('created_at', ListToolbar::SORT_ASC, __('Oldest first')),
            new ListSort('name', ListToolbar::SORT_ASC, __('Name, A to Z')),
            new ListSort('reviewed_at', ListToolbar::SORT_DESC, __('Recently reviewed')),
        ];
    }

    protected function listCount(array $criteria, string $search): int
    {
        return $this->service()->countFiltered($search, $criteria);
    }

    /**
     * @return list<string>
     */
    public static function tabs(): array
    {
        return [CommunityStatus::Pending->value, CommunityStatus::Approved->value, CommunityStatus::Suspended->value, CommunityStatus::Rejected->value];
    }

    /**
     * @return list<string>
     */
    public static function actions(): array
    {
        return [self::ACTION_APPROVE, self::ACTION_REJECT, self::ACTION_SUSPEND, self::ACTION_REINSTATE, self::ACTION_REMOVE];
    }

    public function decide(string $action, int $id): void
    {
        if (! in_array($action, self::actions(), true)) {
            return;
        }

        $user = $this->service()->find($id);

        if ($user === null) {
            return;
        }

        $this->authorize('review', $user);

        $this->decidingAction = $action;
        $this->decidingId = $id;
        $this->note = '';
        $this->resetErrorBag();
    }

    public function cancelDecision(): void
    {
        $this->decidingAction = null;
        $this->decidingId = null;
        $this->note = '';
        $this->resetErrorBag();
    }

    public function isDeciding(string $action): bool
    {
        return $this->decidingAction === $action && $this->decidingId !== null;
    }

    public function confirmDecision(): void
    {
        if ($this->decidingId === null || $this->decidingAction === null) {
            return;
        }

        $user = $this->service()->find($this->decidingId);

        if ($user === null) {
            $this->cancelDecision();

            return;
        }

        $this->authorize('review', $user);

        $request = new CommunityReviewRequest;
        $validated = Validator::make(['note' => $this->note], $request->rules(), $request->messages())->validate();
        $note = filled($validated['note'] ?? null) ? trim((string) $validated['note']) : null;
        $actor = auth()->user();

        try {
            $message = match ($this->decidingAction) {
                self::ACTION_APPROVE => $this->approve($user, $actor, $note),
                self::ACTION_REJECT => $this->reject($user, $actor, $note),
                self::ACTION_SUSPEND => $this->suspend($user, $actor, $note),
                self::ACTION_REINSTATE => $this->reinstate($user, $actor, $note),
                self::ACTION_REMOVE => $this->remove($user, $actor, $note),
                default => null,
            };
        } catch (CommunityApplicationException $exception) {
            $this->addError('note', $exception->getMessage());

            return;
        }

        $this->cancelDecision();

        if ($message !== null) {
            $this->toast($message);
        }
    }

    public function render(): View
    {
        $users = $this->service()->filter($this->search, $this->listCriteria(), self::PER_PAGE, $this->sort ?: null, $this->sort === '' ? ListToolbar::SORT_DESC : $this->direction);
        $isMembersPage = $this->membersPage;

        return view('livewire.admin.community.applications', [
            'users' => $users,
            'toolbar' => $this->listToolbar($users->total()),
            'current' => CommunityStatus::tryFrom((string) ($this->filters[UserRepositoryInterface::FILTER_COMMUNITY] ?? '')),
            'filtered' => $this->listFiltered() && $this->activeView() === '',
            'deciding' => $this->decidingId === null ? null : $this->service()->find($this->decidingId),
            'dialog' => $this->dialogCopy(),
        ])->layout('components.layouts.admin', [
            'title' => AdminNavigation::get($isMembersPage ? AdminNavigation::MEMBERS : AdminNavigation::APPLICATIONS)['label'],
        ]);
    }

    /**
     * @return array{title: string, body: string, confirm: string}|null
     */
    public function dialogCopy(): ?array
    {
        return match ($this->decidingAction) {
            self::ACTION_APPROVE => ['title' => __('Approve this application?'), 'body' => __('They become a member and can use the chat straight away. They will be emailed.'), 'confirm' => __('Approve'), 'danger' => false],
            self::ACTION_REJECT => ['title' => __('Reject this application?'), 'body' => __('They will be emailed. You can still approve them later from the Rejected tab.'), 'confirm' => __('Reject'), 'danger' => true],
            self::ACTION_SUSPEND => ['title' => __('Suspend this member?'), 'body' => __('Their chat access ends immediately and they will be emailed. You can reinstate them later.'), 'confirm' => __('Suspend'), 'danger' => true],
            self::ACTION_REINSTATE => ['title' => __('Reinstate this member?'), 'body' => __('Their chat access returns immediately and they will be emailed.'), 'confirm' => __('Reinstate'), 'danger' => false],
            self::ACTION_REMOVE => ['title' => __('Remove this member?'), 'body' => __('Their membership ends and they will be emailed. Their account and messages remain; they may apply again.'), 'confirm' => __('Remove'), 'danger' => true],
            default => null,
        };
    }

    private function approve(User $user, User $actor, ?string $note): string
    {
        $this->service()->approve($user, $actor, $note);

        return __(':name has been approved and emailed.', ['name' => $user->name]);
    }

    private function reject(User $user, User $actor, ?string $note): string
    {
        $this->service()->reject($user, $actor, $note);

        return __(':name\'s application has been rejected and they have been emailed.', ['name' => $user->name]);
    }

    private function suspend(User $user, User $actor, ?string $note): string
    {
        $this->service()->suspend($user, $actor, $note);

        return __(':name has been suspended.', ['name' => $user->name]);
    }

    private function reinstate(User $user, User $actor, ?string $note): string
    {
        $this->service()->reinstate($user, $actor, $note);

        return __(':name has been reinstated.', ['name' => $user->name]);
    }

    private function remove(User $user, User $actor, ?string $note): string
    {
        $this->service()->remove($user, $actor, $note);

        return __(':name has been removed from the community.', ['name' => $user->name]);
    }

    private function service(): CommunityService
    {
        return app(CommunityService::class);
    }
}
