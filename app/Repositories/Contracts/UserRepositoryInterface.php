<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Constants\CommunityStatus;
use App\Constants\Theme;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<User>
 */
interface UserRepositoryInterface extends BaseRepositoryInterface
{
    public function findByEmail(string $email): ?User;

    /**
     * Clear the verification timestamp (after an email change).
     */
    public function markEmailUnverified(User $user): User;

    /**
     * Stamp the email as verified (after an invitation link has been used).
     */
    public function markEmailVerified(User $user): User;

    /**
     * Keep the user's colour theme choice.
     */
    public function updateTheme(User $user, Theme $theme): User;

    /**
     * Users holding the role whose account is not deactivated, optionally
     * leaving one user out of the count.
     */
    public function countActiveWithRole(string $role, ?int $excludeId = null): int;

    /**
     * Staff and community users for the Users & roles screen: name or email
     * search, optional role and community status filters, by name.
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function search(?string $term = null, ?string $role = null, ?CommunityStatus $status = null, int $perPage = 15): LengthAwarePaginator;

    /**
     * Users holding the given Spatie role.
     *
     * @return Collection<int, User>
     */
    public function withRole(string $role): Collection;

    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function paginateByRole(?string $role = null, int $perPage = 15): LengthAwarePaginator;

    public function countByRole(string $role): int;

    /**
     * @return Collection<int, User>
     */
    public function withCommunityStatus(CommunityStatus $status): Collection;

    public function countByCommunityStatus(CommunityStatus $status): int;

    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function paginateByCommunityStatus(?CommunityStatus $status = null, int $perPage = 15): LengthAwarePaginator;
}
