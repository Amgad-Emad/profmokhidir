<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Constants\AccountState;
use App\Constants\CommunityStatus;
use App\Constants\Roles;
use App\Constants\Theme;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Exceptions\RoleDoesNotExist;

/**
 * @extends BaseEloquentRepository<User>
 */
class EloquentUserRepository extends BaseEloquentRepository implements UserRepositoryInterface
{
    public function __construct(User $user)
    {
        parent::__construct($user);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->query()->where('email', mb_strtolower(trim($email)))->first();
    }

    public function markEmailUnverified(User $user): User
    {
        $user->forceFill(['email_verified_at' => null])->save();

        return $user->refresh();
    }

    public function markEmailVerified(User $user): User
    {
        $user->forceFill(['email_verified_at' => now()])->save();

        return $user->refresh();
    }

    public function updateTheme(User $user, Theme $theme): User
    {
        $user->forceFill(['theme' => $theme])->save();

        return $user->refresh();
    }

    public function updateLocale(User $user, string $locale): User
    {
        $user->forceFill(['locale' => $locale])->save();

        return $user->refresh();
    }

    public function dismissTwoFactorReminder(User $user): User
    {
        $user->forceFill(['two_factor_reminder_dismissed_at' => now()])->save();

        return $user->refresh();
    }

    public function countActiveWithRole(string $role, ?int $excludeId = null): int
    {
        try {
            return $this->query()
                ->role($role)
                ->whereNull('deactivated_at')
                ->when($excludeId !== null, fn ($query) => $query->whereKeyNot($excludeId))
                ->count();
        } catch (RoleDoesNotExist) {
            return 0;
        }
    }

    public function search(?string $term = null, ?string $role = null, ?CommunityStatus $status = null, int $perPage = 15): LengthAwarePaginator
    {
        $term = trim((string) $term);

        try {
            return $this->query()
                ->when($term !== '', fn ($query) => $query->where(function ($query) use ($term): void {
                    $query->where('name', 'like', '%'.$term.'%')->orWhere('email', 'like', '%'.$term.'%');
                }))
                ->when($role !== null, fn ($query) => $query->role($role))
                ->when($status !== null, fn ($query) => $query->communityStatus($status))
                ->with('roles')
                ->orderBy('name')
                ->orderBy('id')
                ->paginate($perPage);
        } catch (RoleDoesNotExist) {
            return $this->query()->whereRaw('1 = 0')->paginate($perPage);
        }
    }

    public function filter(?string $term, array $criteria, int $perPage = 15, ?string $sortBy = null, string $sortDirection = 'asc'): LengthAwarePaginator
    {
        $sortBy = in_array($sortBy, self::SORT_COLUMNS, true) ? $sortBy : 'name';
        $direction = $sortDirection === 'desc' ? 'desc' : 'asc';

        try {
            return $this->filtered($term, $criteria)
                ->with('roles')
                ->orderBy($sortBy, $direction)
                ->orderBy('id', $direction)
                ->paginate($perPage)
                ->withQueryString();
        } catch (RoleDoesNotExist) {
            return $this->query()->whereRaw('1 = 0')->paginate($perPage);
        }
    }

    public function countFiltered(?string $term, array $criteria): int
    {
        try {
            return $this->filtered($term, $criteria)->count();
        } catch (RoleDoesNotExist) {
            return 0;
        }
    }

    /**
     * @param  array<string, string|list<string>>  $criteria
     * @return Builder<User>
     */
    private function filtered(?string $term, array $criteria): Builder
    {
        $term = trim((string) $term);
        $list = fn (string $key): array => array_values(array_filter((array) ($criteria[$key] ?? []), fn ($value): bool => $value !== null && $value !== ''));
        $roles = array_values(array_intersect($list(self::FILTER_ROLE), Roles::all()));
        $accounts = array_values(array_filter(array_map(AccountState::tryFrom(...), $list(self::FILTER_ACCOUNT))));
        $community = array_values(array_intersect($list(self::FILTER_COMMUNITY), CommunityStatus::values()));
        $twoFactor = $list(self::FILTER_TWO_FACTOR)[0] ?? null;
        $email = $list(self::FILTER_EMAIL)[0] ?? null;

        return $this->query()
            ->when($term !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($term): void {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $query->where('name', 'like', $like)->orWhere('email', 'like', $like);
            }))
            ->when($roles !== [], fn (Builder $query) => $query->role($roles))
            // Roles asked for that do not exist match nobody (rather than everybody).
            ->when($roles === [] && $list(self::FILTER_ROLE) !== [], fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->when($accounts !== [], fn (Builder $query) => $query->where(function (Builder $query) use ($accounts): void {
                foreach ($accounts as $account) {
                    $query->orWhere(fn (Builder $query) => $this->whereAccount($query, $account));
                }
            }))
            ->when($community !== [], fn (Builder $query) => $query->whereIn('community_status', $community))
            ->when($twoFactor === self::YES, fn (Builder $query) => $query->whereNotNull('two_factor_confirmed_at'))
            ->when($twoFactor === self::NO, fn (Builder $query) => $query->whereNull('two_factor_confirmed_at'))
            ->when($email === self::YES, fn (Builder $query) => $query->whereNotNull('email_verified_at'))
            ->when($email === self::NO, fn (Builder $query) => $query->whereNull('email_verified_at'));
    }

    /**
     * @param  Builder<User>  $query
     */
    private function whereAccount(Builder $query, AccountState $account): void
    {
        match ($account) {
            AccountState::Deactivated => $query->whereNotNull('deactivated_at'),
            AccountState::Active => $query->whereNull('deactivated_at')->whereNotNull('email_verified_at'),
            AccountState::Invited => $query->whereNull('deactivated_at')->whereNull('email_verified_at')->role(Roles::staff()),
            AccountState::Unverified => $query->whereNull('deactivated_at')->whereNull('email_verified_at')->withoutRole(Roles::staff()),
        };
    }

    public function withRole(string $role): Collection
    {
        try {
            return $this->query()->role($role)->with('roles')->orderBy('name')->get();
        } catch (RoleDoesNotExist) {
            return new Collection;
        }
    }

    public function paginateByRole(?string $role = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->when($role !== null, fn ($query) => $query->role($role))
            ->with('roles')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function countByRole(string $role): int
    {
        return $this->query()->role($role)->count();
    }

    public function withCommunityStatus(CommunityStatus $status): Collection
    {
        return $this->query()->communityStatus($status)->with('roles')->orderBy('name')->get();
    }

    public function countByCommunityStatus(CommunityStatus $status): int
    {
        return $this->query()->communityStatus($status)->count();
    }

    public function paginateByCommunityStatus(?CommunityStatus $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->when($status !== null, fn ($query) => $query->communityStatus($status))
            ->with('roles')
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}
