<?php

namespace App\Providers;

use App\Constants\ChatSettings;
use App\Constants\RateLimits;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureCommunityMember;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::define('access-admin', fn (User $user): bool => $user->canAccessAdmin());

        // Public community signups: a handful per address per hour.
        RateLimiter::for(RateLimits::COMMUNITY_SIGNUP, function (Request $request) {
            return Limit::perHour(RateLimits::COMMUNITY_SIGNUP_PER_HOUR)->by((string) $request->ip());
        });

        // Chat sends per member per minute (ChatService applies the same limiter directly).
        RateLimiter::for(RateLimits::CHAT_SEND, function (Request $request) {
            return Limit::perMinute(ChatSettings::SEND_LIMIT_PER_MINUTE)->by((string) ($request->user()?->getKey() ?? $request->ip()));
        });

        // Livewire re-applies these route middleware on every component update,
        // so a suspended member is refused on the very next poll or send.
        Livewire::addPersistentMiddleware([EnsureCommunityMember::class, EnsureAccountIsActive::class]);

        // The mail sender comes from the site settings. It is applied when the mail
        // manager is first resolved (not at boot, which would fix the cache store
        // too early); before the settings table exists config stays as it is.
        $this->app->afterResolving('mail.manager', function (): void {
            try {
                $this->app->make(SettingService::class)->applyMailSender();
            } catch (QueryException|\PDOException) {
                // Not migrated yet.
            }
        });
    }
}
