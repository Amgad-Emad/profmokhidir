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
    public const string FILTER_ROLE = 'role';

    public const string FILTER_ACCOUNT = 'account';

    public const string FILTER_COMMUNITY = 'community';

    public const string FILTER_TWO_FACTOR = 'two_factor';

    public const string FILTER_EMAIL = 'email';

    /** Values of FILTER_TWO_FACTOR and FILTER_EMAIL. */
    public const string YES = 'on';

    public const string NO = 'off';

    /** Columns filter() sorts by besides the name. */
    public const array SORT_COLUMNS = ['name', 'created_at', 'reviewed_at'];

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
     * Keep the user's language choice (App\Constants\Locales).
     */
    public function updateLocale(User $user, string $locale): User;

    /**
     * Remember that the user dismissed the dashboard card recommending two-factor authentication.
     */
    public function dismissTwoFactorReminder(User $user): User;

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
     * Users for an admin list: name or email search, then any of these
     * criteria (FILTER_* keys; lists match any of their values): roles,
     * AccountState, CommunityStatus, two-factor on or off, email verified or not.
     * Sorted by name unless `sortBy` is one of SORT_COLUMNS.
     *
     * @param  array<string, string|list<string>>  $criteria
     * @return LengthAwarePaginator<int, User>
     */
    public function filter(?string $term, array $criteria, int $perPage = 15, ?string $sortBy = null, string $sortDirection = 'asc'): LengthAwarePaginator;

    /**
     * @param  array<string, string|list<string>>  $criteria
     */
    public function countFiltered(?string $term, array $criteria): int;

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
