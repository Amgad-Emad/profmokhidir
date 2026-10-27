<?php

namespace App\Providers;

use App\Constants\ChatSettings;
use App\Constants\RateLimits;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureCommunityMember;
use App\Models\User;
use App\Services\PageContentService;
use App\Services\SettingService;
use App\Services\TypesetterService;
use App\Support\LocalizedUrlGenerator;
use App\Support\PreviewContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\DevCommands;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // The draft shown by a signed preview link lives for one request only.
        $this->app->scoped(PreviewContext::class);

        // Page copy is read many times per request (header, footer, page): loaded once per request.
        $this->app->scoped(PageContentService::class);

        // route() stays in the language of the page (/ar/... while Arabic is current). Rebinding the
        // singleton keeps the framework's extenders, which give the generator its session and key resolvers.
        $this->app->singleton('url', function ($app) {
            $routes = $app['router']->getRoutes();
            $app->instance('routes', $routes);

            return new LocalizedUrlGenerator(
                $routes,
                $app->rebinding('request', fn ($app, $request) => $app['url']->setRequest($request)),
                $app['config']['app.asset_url'],
            );
        });
    }

    public function boot(): void
    {
        // Carbon keeps its locale in a static: start every application in the configured language
        // (SetLocale switches it per request).
        Carbon::setLocale($this->app->getLocale());

        Gate::define('access-admin', fn (User $user): bool => $user->canAccessAdmin());

        $this->registerDevCommands();

        // @typeset($text): database text with curly quotes, en dashes, non-breaking spaces and small caps, escaped.
        Blade::directive('typeset', fn (string $expression): string => '<?php echo app(\\'.TypesetterService::class.'::class)->inline('.$expression.'); ?>');

        // @contentLang($model) or @contentLang($model, 'summary'): `lang="en" dir="ltr"` on an element whose
        // text falls back to English on an Arabic page; nothing when it is in the page's own language.
        Blade::directive('contentLang', fn (string $expression): string => '<?php echo \\App\\Constants\\Locales::attributesFor('.$expression.'); ?>');

        // @num($count): a number inside running text, in Arabic-Indic digits on Arabic pages when the site
        // setting asks for them (Western digits otherwise, and always for years, citations, DOIs and data).
        Blade::directive('num', fn (string $expression): string => '<?php echo e(app(\\App\\Services\\LocaleService::class)->digits('.$expression.')); ?>');

        // Public community signups: a handful per address per hour.
        RateLimiter::for(RateLimits::COMMUNITY_SIGNUP, function (Request $request) {
            return Limit::perHour(RateLimits::COMMUNITY_SIGNUP_PER_HOUR)->by((string) $request->ip());
        });

        // Chat sends per member per minute (ChatService applies the same limiter directly).
        RateLimiter::for(RateLimits::CHAT_SEND, function (Request $request) {
            return Limit::perMinute(ChatSettings::SEND_LIMIT_PER_MINUTE)->by((string) ($request->user()?->getKey() ?? $request->ip()));
        });

        // Chat upload requests per member per minute (each staged file also counts as a send).
        RateLimiter::for(RateLimits::CHAT_UPLOAD, function (Request $request) {
            return Limit::perMinute(RateLimits::CHAT_UPLOAD_PER_MINUTE)->by((string) ($request->user()?->getKey() ?? $request->ip()));
        });

        // Analytics beacons per address per minute. The key is an HMAC of the address, so even the
        // short-lived limiter entry never holds the IP itself.
        RateLimiter::for(RateLimits::ANALYTICS_COLLECT, function (Request $request) {
            return Limit::perMinute(RateLimits::ANALYTICS_COLLECT_PER_MINUTE)->by(hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')));
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

    /**
     * What `composer run dev` (php artisan dev) starts. Valet serves the site, so there is no
     * `artisan serve`; the queue worker is what makes chat and media thumbnails and sends mail
     * (QUEUE_CONNECTION=database), next to the log tail and Vite.
     */
    private function registerDevCommands(): void
    {
        DevCommands::withoutDefaultCommands();
        DevCommands::artisan('queue:listen --tries=1 --timeout=0', 'queue');

        if (function_exists('pcntl_fork')) {
            DevCommands::artisan('pail --timeout=0', 'logs');
        }

        DevCommands::node('dev', 'vite');
    }
}
