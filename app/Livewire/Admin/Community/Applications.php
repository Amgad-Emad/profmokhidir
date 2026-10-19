<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Community;

use App\Constants\AdminNavigation;
use App\Constants\CommunityStatus;
use App\Constants\StatusTone;
use App\Exceptions\CommunityApplicationException;
use App\Http\Requests\Admin\CommunityReviewRequest;
use App\Models\User;
use App\Services\CommunityService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The community approval queue and member list: Pending, Approved
 * (members), Suspended and Rejected tabs. Every decision takes an optional
 * note that is emailed to the person and kept in the audit log.
 */
class Applications extends Component
{
    use AuthorizesRequests, WithPagination;

    public const string ACTION_APPROVE = 'approve';

    public const string ACTION_REJECT = 'reject';

    public const string ACTION_SUSPEND = 'suspend';

    public const string ACTION_REINSTATE = 'reinstate';

    public const string ACTION_REMOVE = 'remove';

    public const int PER_PAGE = 15;

    #[Url(except: 'pending')]
    public string $tab = CommunityStatus::Pending->value;

    /** The user a decision is being confirmed for, and which decision. */
    public ?int $decidingId = null;

    public ?string $decidingAction = null;

    public string $note = '';

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);

        if (request()->routeIs(AdminNavigation::routeFor(AdminNavigation::MEMBERS)) && ! request()->has('tab')) {
            $this->tab = CommunityStatus::Approved->value;
        }

        if (! in_array($this->tab, self::tabs(), true)) {
            $this->tab = CommunityStatus::Pending->value;
        }
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

    public function showTab(string $tab): void
    {
        if (in_array($tab, self::tabs(), true)) {
            $this->tab = $tab;
            $this->resetPage();
        }
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
            session()->flash('admin.status', $message);
        }
    }

    public function render(): View
    {
        $tabs = [];

        foreach (self::tabs() as $value) {
            $status = CommunityStatus::from($value);
            $tabs[$value] = [
                'label' => $status === CommunityStatus::Approved ? 'Members' : $status->label(),
                'count' => $this->service()->countByStatus($status),
                'tone' => StatusTone::forCommunity($status),
            ];
        }

        $isMembersPage = request()->routeIs(AdminNavigation::routeFor(AdminNavigation::MEMBERS));

        return view('livewire.admin.community.applications', [
            'users' => $this->service()->paginate($this->tab, self::PER_PAGE),
            'tabs' => $tabs,
            'current' => CommunityStatus::from($this->tab),
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
            self::ACTION_APPROVE => ['title' => 'Approve this application?', 'body' => 'They become a member and can use the chat straight away. They will be emailed.', 'confirm' => 'Approve'],
            self::ACTION_REJECT => ['title' => 'Reject this application?', 'body' => 'They will be emailed. You can still approve them later from the Rejected tab.', 'confirm' => 'Reject'],
            self::ACTION_SUSPEND => ['title' => 'Suspend this member?', 'body' => 'Their chat access ends immediately and they will be emailed. You can reinstate them later.', 'confirm' => 'Suspend'],
            self::ACTION_REINSTATE => ['title' => 'Reinstate this member?', 'body' => 'Their chat access returns immediately and they will be emailed.', 'confirm' => 'Reinstate'],
            self::ACTION_REMOVE => ['title' => 'Remove this member?', 'body' => 'Their membership ends and they will be emailed. Their account and messages remain; they may apply again.', 'confirm' => 'Remove'],
            default => null,
        };
    }

    private function approve(User $user, User $actor, ?string $note): string
    {
        $this->service()->approve($user, $actor, $note);

        return $user->name.' has been approved and emailed.';
    }

    private function reject(User $user, User $actor, ?string $note): string
    {
        $this->service()->reject($user, $actor, $note);

        return $user->name.'\'s application has been rejected and they have been emailed.';
    }

    private function suspend(User $user, User $actor, ?string $note): string
    {
        $this->service()->suspend($user, $actor, $note);

        return $user->name.' has been suspended.';
    }

    private function reinstate(User $user, User $actor, ?string $note): string
    {
        $this->service()->reinstate($user, $actor, $note);

        return $user->name.' has been reinstated.';
    }

    private function remove(User $user, User $actor, ?string $note): string
    {
        $this->service()->remove($user, $actor, $note);

        return $user->name.' has been removed from the community.';
    }

    private function service(): CommunityService
    {
        return app(CommunityService::class);
    }
}
