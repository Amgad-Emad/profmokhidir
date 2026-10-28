<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\AuditAction;
use App\Constants\CommunityStatus;
use App\Constants\Roles;
use App\Exceptions\CommunityApplicationException;
use App\Models\User;
use App\Notifications\CommunityApplicationReceived;
use App\Notifications\CommunityApplicationReviewed;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Scientific Community applications and membership. Signing up creates an
 * applicant; approval grants the member role; rejection, suspension and
 * removal take it away. Every decision is audited and the applicant is told
 * by email; new applications are announced to the notification address from
 * the site settings (or to every administrator when none is set).
 */
class CommunityService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly UserService $userService,
        private readonly RoleService $roles,
        private readonly AuditLogService $audit,
        private readonly SettingService $settings,
    ) {}

    /**
     * Public signup: create the account as an applicant, record the
     * application and send the email-verification link.
     *
     * @param  array{name: string, email: string, password: string, locale?: ?string, affiliation?: ?string, academic_field?: ?string, reason_for_joining?: ?string}  $data
     */
    public function register(array $data): User
    {
        $user = $this->userService->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => Roles::APPLICANT,
            // Emails (verification, the decision) follow the language the person signed up in.
            'locale' => $data['locale'] ?? app()->getLocale(),
        ]);

        $user = $this->apply($user, $data);

        event(new Registered($user));

        return $user;
    }

    /**
     * @param  array{affiliation?: ?string, academic_field?: ?string, reason_for_joining?: ?string}  $data
     */
    public function apply(User $user, array $data): User
    {
        $from = $user->community_status;

        if (in_array($from, [CommunityStatus::Pending, CommunityStatus::Approved], true)) {
            throw CommunityApplicationException::invalidTransition($from, CommunityStatus::Pending);
        }

        $user = $this->users->update($user, [
            'community_status' => CommunityStatus::Pending,
            'affiliation' => $this->clean($data['affiliation'] ?? null),
            'academic_field' => $this->clean($data['academic_field'] ?? null),
            'reason_for_joining' => $this->clean($data['reason_for_joining'] ?? null),
            'reviewed_by' => null,
            'reviewed_at' => null,
            'review_note' => null,
        ]);

        if (! $user->canAccessAdmin() && ! $user->isMember() && ! $user->hasRole(Roles::APPLICANT)) {
            $this->roles->assign($user, Roles::APPLICANT, $user);
        }

        $this->audit->record($user, AuditAction::COMMUNITY_APPLIED, $user);
        $this->announceApplication($user);

        return $user;
    }

    /**
     * Tell the notification address, or every administrator when none is set,
     * that an application is waiting.
     */
    private function announceApplication(User $applicant): void
    {
        $notification = new CommunityApplicationReceived($applicant);
        $address = $this->settings->notificationAddress();

        if ($address !== null) {
            Notification::route('mail', $address)->notify($notification);

            return;
        }

        Notification::send($this->users->withRole(Roles::ADMIN), $notification);
    }

    public function approve(User $applicant, User $actor, ?string $note = null): User
    {
        $this->guardTransition($applicant->community_status, CommunityStatus::Approved, [
            CommunityStatus::Pending, CommunityStatus::Rejected, CommunityStatus::Suspended,
        ]);

        $applicant = $this->review($applicant, $actor, CommunityStatus::Approved, $note);
        $this->grantMembership($applicant, $actor);

        $this->audit->record($actor, AuditAction::COMMUNITY_APPROVED, $applicant, array_filter(['note' => $note]));
        $applicant->notify(new CommunityApplicationReviewed(CommunityStatus::Approved, $this->clean($note)));

        return $applicant->refresh();
    }

    public function reject(User $applicant, User $actor, ?string $note = null): User
    {
        $this->guardTransition($applicant->community_status, CommunityStatus::Rejected, [
            CommunityStatus::Pending,
        ]);

        $applicant = $this->review($applicant, $actor, CommunityStatus::Rejected, $note);

        $this->audit->record($actor, AuditAction::COMMUNITY_REJECTED, $applicant, array_filter(['note' => $note]));
        $applicant->notify(new CommunityApplicationReviewed(CommunityStatus::Rejected, $this->clean($note)));

        return $applicant->refresh();
    }

    /**
     * Take the member role away; the next chat request is refused.
     */
    public function suspend(User $member, User $actor, ?string $note = null): User
    {
        $this->guardTransition($member->community_status, CommunityStatus::Suspended, [
            CommunityStatus::Approved,
        ]);

        $member = $this->review($member, $actor, CommunityStatus::Suspended, $note);

        if ($member->isMember()) {
            $this->roles->remove($member, Roles::MEMBER, $actor);
        }

        $this->audit->record($actor, AuditAction::COMMUNITY_SUSPENDED, $member, array_filter(['note' => $note]));
        $member->notify(new CommunityApplicationReviewed(CommunityStatus::Suspended, $this->clean($note)));

        return $member->refresh();
    }

    /**
     * Give a suspended member their access back.
     */
    public function reinstate(User $member, User $actor, ?string $note = null): User
    {
        $this->guardTransition($member->community_status, CommunityStatus::Approved, [
            CommunityStatus::Suspended,
        ]);

        $member = $this->review($member, $actor, CommunityStatus::Approved, $note);
        $this->grantMembership($member, $actor);

        $this->audit->record($actor, AuditAction::COMMUNITY_REINSTATED, $member, array_filter(['note' => $note]));
        $member->notify(new CommunityApplicationReviewed(CommunityStatus::Approved, $this->clean($note)));

        return $member->refresh();
    }

    /**
     * End a membership: the account stays (so their messages keep their
     * author) but every community role goes and the status returns to "not
     * applied". They may apply again.
     */
    public function remove(User $member, User $actor, ?string $note = null): User
    {
        $this->guardTransition($member->community_status, CommunityStatus::None, [
            CommunityStatus::Approved, CommunityStatus::Suspended, CommunityStatus::Rejected, CommunityStatus::Pending,
        ]);

        $member = $this->review($member, $actor, CommunityStatus::None, $note);

        foreach ([Roles::MEMBER, Roles::APPLICANT] as $role) {
            if ($member->hasRole($role)) {
                $this->roles->remove($member, $role, $actor);
            }
        }

        $this->audit->record($actor, AuditAction::COMMUNITY_REMOVED, $member, array_filter(['note' => $note]));
        $member->notify(new CommunityApplicationReviewed(CommunityStatus::None, $this->clean($note)));

        return $member->refresh();
    }

    /**
     * @return Collection<int, User>
     */
    public function pendingApplicants(): Collection
    {
        return $this->users->withCommunityStatus(CommunityStatus::Pending);
    }

    public function countPending(): int
    {
        return $this->users->countByCommunityStatus(CommunityStatus::Pending);
    }

    public function countByStatus(CommunityStatus $status): int
    {
        return $this->users->countByCommunityStatus($status);
    }

    /**
     * @return Collection<int, User>
     */
    public function members(): Collection
    {
        return $this->users->withCommunityStatus(CommunityStatus::Approved);
    }

    public function find(int $id): ?User
    {
        return $this->users->find($id);
    }

    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        $statusEnum = $status !== null && $status !== '' ? CommunityStatus::from($status) : null;

        return $this->users->paginateByCommunityStatus($statusEnum, $perPage);
    }

    /**
     * Applicants and members for the community screen: search and
     * UserRepositoryInterface::FILTER_* criteria, limited to accounts that have
     * applied (any status but None) unless a community status is chosen.
     *
     * @param  array<string, string|list<string>>  $criteria
     * @return LengthAwarePaginator<int, User>
     */
    public function filter(?string $term, array $criteria, int $perPage = 15, ?string $sortBy = null, string $sortDirection = 'desc'): LengthAwarePaginator
    {
        return $this->users->filter($term, $this->applied($criteria), $perPage, $sortBy ?? 'created_at', $sortDirection);
    }

    /**
     * @param  array<string, string|list<string>>  $criteria
     */
    public function countFiltered(?string $term, array $criteria): int
    {
        return $this->users->countFiltered($term, $this->applied($criteria));
    }

    /**
     * @param  array<string, string|list<string>>  $criteria
     * @return array<string, string|list<string>>
     */
    private function applied(array $criteria): array
    {
        $criteria[UserRepositoryInterface::FILTER_COMMUNITY] ??= array_values(array_diff(CommunityStatus::values(), [CommunityStatus::None->value]));

        return $criteria;
    }

    private function grantMembership(User $user, User $actor): void
    {
        if ($user->hasRole(Roles::APPLICANT)) {
            $this->roles->remove($user, Roles::APPLICANT, $actor);
        }

        if (! $user->isMember()) {
            $this->roles->assign($user, Roles::MEMBER, $actor);
        }
    }

    private function review(User $user, User $actor, CommunityStatus $status, ?string $note): User
    {
        return $this->users->update($user, [
            'community_status' => $status,
            'reviewed_by' => $actor->getKey(),
            'reviewed_at' => now(),
            'review_note' => $this->clean($note),
        ]);
    }

    /**
     * @param  list<CommunityStatus>  $allowedFrom
     */
    private function guardTransition(CommunityStatus $from, CommunityStatus $to, array $allowedFrom): void
    {
        if (! in_array($from, $allowedFrom, true)) {
            throw CommunityApplicationException::invalidTransition($from, $to);
        }
    }

    private function clean(?string $value): ?string
    {
        $value = $value === null ? null : trim($value);

        return $value === '' ? null : $value;
    }
}
