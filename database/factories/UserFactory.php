<?php

namespace Database\Factories;

use App\Constants\CommunityStatus;
use App\Constants\Roles;
use App\Models\User;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Services\RoleService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\RecoveryCode;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'community_status' => CommunityStatus::None,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Give the user a Spatie role (created with its permissions if missing).
     */
    public function withRole(string $role): static
    {
        return $this->afterCreating(function (User $user) use ($role): void {
            app(RoleService::class)->ensureRole($role);
            app(RoleRepositoryInterface::class)->assign($user, $role);
            $user->unsetRelation('roles');
        });
    }

    /**
     * An administrator. Admin accounts must use two-factor authentication, so
     * the state comes with a confirmed TOTP secret; chain withoutTwoFactor()
     * to test the enforcement itself.
     */
    public function admin(): static
    {
        return $this->withRole(Roles::ADMIN)->withTwoFactor();
    }

    /**
     * A confirmed TOTP secret with recovery codes.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt(app(TwoFactorAuthenticationProvider::class)->generateSecretKey()),
            'two_factor_recovery_codes' => encrypt(json_encode(Collection::times(8, fn () => RecoveryCode::generate())->all())),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    /**
     * Two-factor enabled but not yet confirmed with a code.
     */
    public function withPendingTwoFactor(): static
    {
        return $this->withTwoFactor()->state(fn (array $attributes) => [
            'two_factor_confirmed_at' => null,
        ]);
    }

    public function withoutTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
    }

    public function editor(): static
    {
        return $this->withRole(Roles::EDITOR);
    }

    /**
     * An approved community member.
     */
    public function member(): static
    {
        return $this->withRole(Roles::MEMBER)->state(fn (array $attributes) => [
            'community_status' => CommunityStatus::Approved,
            'affiliation' => fake()->company(),
            'academic_field' => fake()->randomElement(['Soil science', 'Agronomy', 'Plant pathology', 'Rural development']),
            'reason_for_joining' => '[Placeholder] '.fake()->sentence(12),
            'reviewed_at' => now()->subDays(fake()->numberBetween(1, 90)),
        ]);
    }

    /**
     * A pending community applicant.
     */
    public function applicant(): static
    {
        return $this->withRole(Roles::APPLICANT)->state(fn (array $attributes) => [
            'community_status' => CommunityStatus::Pending,
            'affiliation' => fake()->company(),
            'academic_field' => fake()->randomElement(['Soil science', 'Agronomy', 'Plant pathology', 'Rural development']),
            'reason_for_joining' => '[Placeholder] '.fake()->sentence(12),
        ]);
    }

    public function rejectedApplicant(): static
    {
        return $this->applicant()->state(fn (array $attributes) => [
            'community_status' => CommunityStatus::Rejected,
            'reviewed_at' => now()->subDays(fake()->numberBetween(1, 30)),
            'review_note' => '[Placeholder] Application did not include an academic affiliation.',
        ]);
    }

    public function suspendedMember(): static
    {
        return $this->withRole(Roles::APPLICANT)->state(fn (array $attributes) => [
            'community_status' => CommunityStatus::Suspended,
            'reviewed_at' => now()->subDays(fake()->numberBetween(1, 30)),
            'review_note' => '[Placeholder] Suspended pending review.',
        ]);
    }

    public function reviewedBy(User $reviewer): static
    {
        return $this->state(fn (array $attributes) => ['reviewed_by' => $reviewer->id]);
    }
}
