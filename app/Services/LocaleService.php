<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Locales;
use App\Constants\SettingKeys;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Support\LocalizedUrlGenerator;
use Illuminate\Contracts\Cookie\Factory as CookieFactory;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * The language of a request and everything that follows from it: which
 * language a page is in (fixed by the address on public pages, the saved
 * choice elsewhere), how a choice is kept (on the user and in a cookie), the
 * same page's address in each language (switcher, hreflang, sitemaps), the
 * quiet suggestion for Arabic-preferring browsers, dates and digits.
 */
class LocaleService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly CookieFactory $cookies,
        private readonly SettingService $settings,
    ) {}

    /**
     * The language a request is served in: a public route's own language (its
     * address decides), else the signed-in user's choice, else the cookie,
     * else English.
     */
    public function resolve(Request $request): string
    {
        $fixed = $this->routeLocale($request->route());

        if ($fixed !== null) {
            return $fixed;
        }

        return $this->preferred($request);
    }

    /**
     * The saved choice: the user's column, else the cookie, else English.
     */
    public function preferred(Request $request): string
    {
        $user = $request->user();

        if ($user instanceof User && Locales::isValid($user->locale)) {
            return $user->locale;
        }

        $cookie = $request->cookie(Locales::COOKIE);

        return Locales::isValid($cookie) ? $cookie : Locales::DEFAULT;
    }

    /**
     * The language a public route is fixed to (null for routes that follow the choice).
     */
    public function routeLocale(?Route $route): ?string
    {
        $locale = $route?->getAction(Locales::ACTION_KEY);

        return Locales::isValid($locale) ? $locale : null;
    }

    public function apply(string $locale): void
    {
        App::setLocale($locale);
        Carbon::setLocale($locale);
    }

    /**
     * Keep a choice: on the account when signed in, and always in the cookie
     * (returned for the response to queue).
     */
    public function remember(string $locale, ?User $user = null): Cookie
    {
        if ($user instanceof User && $user->locale !== $locale) {
            $this->users->updateLocale($user, $locale);
        }

        return $this->cookies->make(
            name: Locales::COOKIE,
            value: $locale,
            minutes: Locales::COOKIE_MINUTES,
            httpOnly: false,
            sameSite: 'lax',
        );
    }

    /**
     * The current page's address in another language, keeping the query
     * string: the twin route of a public page, the same address otherwise.
     */
    public function alternateUrl(Request $request, string $locale): string
    {
        $route = $request->route();
        $name = $route?->getName();

        if ($route === null || $name === null || $this->routeLocale($route) === null || ! $this->url()->hasRouteIn($locale, $name)) {
            return $request->fullUrl();
        }

        $url = $this->url()->routeIn($locale, $name, $route->parameters());
        $query = $request->getQueryString();

        return $query === null ? $url : $url.'?'.$query;
    }

    /**
     * Each language's address of the current page (hreflang alternates), or
     * an empty list for pages that exist in one form only. The query string is
     * left out, as canonical addresses are.
     *
     * @param  list<string>|null  $locales  limit to these (content without an Arabic version lists English alone)
     * @return array<string, string> hreflang => url
     */
    public function alternates(Request $request, ?array $locales = null): array
    {
        $route = $request->route();
        $name = $route?->getName();

        if ($route === null || $name === null || $this->routeLocale($route) === null) {
            return [];
        }

        $alternates = [];

        foreach ($locales ?? Locales::all() as $locale) {
            if ($this->url()->hasRouteIn($locale, $name)) {
                $alternates[Locales::definitions()[$locale]['hreflang']] = $this->url()->routeIn($locale, $name, $route->parameters());
            }
        }

        return $alternates;
    }

    /**
     * The switcher's link: stores the choice, then opens the target address.
     */
    public function switchUrl(string $locale, string $target): string
    {
        return $this->url()->routeIn(Locales::DEFAULT, Locales::SWITCH_ROUTE, ['locale' => $locale, Locales::TARGET_PARAM => $this->relative($target)]);
    }

    /**
     * Where the switcher may send the visitor: a path on this site only
     * (anything else goes to the home page of the language).
     */
    public function safeTarget(?string $target, string $locale): string
    {
        if (is_string($target) && str_starts_with($target, '/') && ! str_starts_with($target, '//') && ! str_contains($target, '\\')) {
            return $target;
        }

        return $this->relative($this->url()->routeIn($locale, 'home'));
    }

    /**
     * Whether to offer the quiet "read the site in Arabic" suggestion: an
     * English public page, no choice made yet, not dismissed, and a browser
     * whose Accept-Language ranks Arabic above English.
     */
    public function shouldSuggest(Request $request): bool
    {
        if (App::getLocale() !== Locales::DEFAULT
            || $this->routeLocale($request->route()) === null
            || $request->cookie(Locales::COOKIE) !== null
            || $request->cookie(Locales::SUGGESTION_COOKIE) !== null
            || ($request->user() instanceof User && $request->user()->locale !== null)) {
            return false;
        }

        return $this->prefersArabic((string) $request->header('Accept-Language', ''));
    }

    /**
     * Whether an Accept-Language header ranks Arabic first among the site's languages.
     */
    public function prefersArabic(string $header): bool
    {
        $ranked = [];

        foreach (explode(',', $header) as $index => $part) {
            $pieces = explode(';', trim($part));
            $tag = strtolower(trim($pieces[0]));
            $quality = 1.0;

            foreach (array_slice($pieces, 1) as $parameter) {
                if (str_starts_with(trim($parameter), 'q=')) {
                    $quality = (float) substr(trim($parameter), 2);
                }
            }

            $language = explode('-', $tag)[0];

            if (Locales::isValid($language) && $quality > 0) {
                // Earlier entries win ties, as browsers list them in order of preference.
                $ranked[] = ['locale' => $language, 'quality' => $quality, 'index' => $index];
            }
        }

        usort($ranked, fn (array $a, array $b): int => [$b['quality'], $a['index']] <=> [$a['quality'], $b['index']]);

        return ($ranked[0]['locale'] ?? null) === Locales::AR;
    }

    /**
     * A date in the current language, Gregorian calendar, Western digits
     * ("12 March 2024" / "12 مارس 2024").
     */
    public function date(?\DateTimeInterface $date, string $format = 'j F Y'): string
    {
        return $date === null ? '' : Carbon::instance($date)->locale(App::getLocale())->translatedFormat($format);
    }

    /**
     * Running text in Arabic-Indic digits when the client has chosen them for
     * Arabic pages (Admin › Settings); Western digits otherwise.
     */
    public function digits(string|int $text): string
    {
        $text = (string) $text;

        if (App::getLocale() !== Locales::AR || $this->digitStyle() !== Locales::DIGITS_ARABIC) {
            return $text;
        }

        return strtr($text, ['0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤', '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩']);
    }

    public function digitStyle(): string
    {
        $style = $this->settings->get(SettingKeys::ARABIC_DIGITS);

        return in_array($style, Locales::digitStyles(), true) ? $style : Locales::DIGITS_LATIN;
    }

    private function relative(string $url): string
    {
        $parts = parse_url($url);
        $path = $parts['path'] ?? '/';

        return $path.(isset($parts['query']) ? '?'.$parts['query'] : '');
    }

    private function url(): LocalizedUrlGenerator
    {
        /** @var LocalizedUrlGenerator */
        return app('url');
    }
}
