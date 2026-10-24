<?php

namespace App\Models;

use App\Constants\CommunityStatus;
use App\Constants\Roles;
use App\Constants\Theme;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * Roles come from spatie/laravel-permission (HasRoles); community membership
 * state lives in community_status.
 */
#[Fillable([
    'name', 'email', 'password',
    'community_status', 'affiliation', 'academic_field', 'reason_for_joining',
    'reviewed_by', 'reviewed_at', 'review_note', 'deactivated_at', 'theme',
])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_reminder_dismissed_at' => 'datetime',
            'community_status' => CommunityStatus::class,
            'reviewed_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'theme' => Theme::class,
        ];
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reviewed_by');
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * @param  Builder<User>  $query
     */
    #[Scope]
    protected function communityStatus(Builder $query, CommunityStatus $status): void
    {
        $query->where('community_status', $status->value);
    }

    /**
     * A deactivated account cannot sign in and is signed out on its next request.
     */
    public function isActive(): bool
    {
        return $this->deactivated_at === null;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Roles::ADMIN);
    }

    public function isEditor(): bool
    {
        return $this->hasRole(Roles::EDITOR);
    }

    public function isMember(): bool
    {
        return $this->hasRole(Roles::MEMBER);
    }

    /**
     * Admins and editors may use the admin dashboard.
     */
    public function canAccessAdmin(): bool
    {
        return $this->hasAnyRole(Roles::staff());
    }

    /**
     * Only approved members (and admins, who moderate) may use the community chat.
     * Checked server side on every chat request.
     */
    public function canAccessChat(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->isMember() && $this->community_status->canChat();
    }

    /**
     * The user's most privileged role name, for display.
     */
    public function primaryRole(): ?string
    {
        $names = $this->getRoleNames();

        foreach (Roles::all() as $role) {
            if ($names->contains($role)) {
                return $role;
            }
        }

        return null;
    }

    public function primaryRoleLabel(): string
    {
        $role = $this->primaryRole();

        return $role === null ? 'User' : (Roles::labels()[$role] ?? $role);
    }
}
