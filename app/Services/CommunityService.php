<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\AuditAction;
use App\Constants\CommunityStatus;
use App\Constants\Roles;
use App\Exceptions\CommunityApplicationException;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Scientific Community applications. Approval grants the member role; rejection
 * and suspension remove it. Every decision is audited.
 */
class CommunityService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly RoleService $roles,
        private readonly AuditLogService $audit,
    ) {}

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

        if (! $user->canAccessAdmin() && ! $user->isMember()) {
            $this->roles->assign($user, Roles::APPLICANT, $user);
        }

        $this->audit->record($user, AuditAction::COMMUNITY_APPLIED, $user);

        return $user;
    }

    public function approve(User $applicant, User $actor, ?string $note = null): User
    {
        $this->guardTransition($applicant->community_status, CommunityStatus::Approved, [
            CommunityStatus::Pending, CommunityStatus::Rejected, CommunityStatus::Suspended,
        ]);

        $applicant = $this->review($applicant, $actor, CommunityStatus::Approved, $note);

        if ($applicant->hasRole(Roles::APPLICANT)) {
            $this->roles->remove($applicant, Roles::APPLICANT, $actor);
        }

        if (! $applicant->isMember()) {
            $this->roles->assign($applicant, Roles::MEMBER, $actor);
        }

        $this->audit->record($actor, AuditAction::COMMUNITY_APPROVED, $applicant, array_filter(['note' => $note]));

        return $applicant->refresh();
    }

    public function reject(User $applicant, User $actor, ?string $note = null): User
    {
        $this->guardTransition($applicant->community_status, CommunityStatus::Rejected, [
            CommunityStatus::Pending,
        ]);

        $applicant = $this->review($applicant, $actor, CommunityStatus::Rejected, $note);

        $this->audit->record($actor, AuditAction::COMMUNITY_REJECTED, $applicant, array_filter(['note' => $note]));

        return $applicant->refresh();
    }

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

    /**
     * @return Collection<int, User>
     */
    public function members(): Collection
    {
        return $this->users->withCommunityStatus(CommunityStatus::Approved);
    }

    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        $statusEnum = $status !== null && $status !== '' ? CommunityStatus::from($status) : null;

        return $this->users->paginateByCommunityStatus($statusEnum, $perPage);
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
