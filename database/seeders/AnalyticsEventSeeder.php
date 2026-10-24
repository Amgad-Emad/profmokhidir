<?php

namespace Database\Seeders;

use App\Constants\Analytics;
use App\Constants\DeviceType;
use App\Constants\Environments;
use App\Constants\Sections;
use App\Repositories\Contracts\AnalyticsEventRepositoryInterface;
use App\Services\SitemapService;
use Carbon\CarbonImmutable;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Local only: realistic raw analytics page views for the last DAYS days (and
 * today so far), on the site's real public pages. Visitors arrive from search,
 * social links and direct, mostly from Sudan and the Gulf, mostly on phones;
 * some return later the same day; reading time depends on the kind of page.
 * AnalyticsDemoSeeder rolls them up. Never runs outside the local environment.
 */
class AnalyticsEventSeeder extends Seeder
{
    public const int DAYS = 90;

    /** Weighted choices: value => weight. */
    private const array COUNTRIES = ['SD' => 34, 'SA' => 12, 'EG' => 9, 'GB' => 7, 'US' => 6, 'AE' => 6, 'QA' => 3, 'DE' => 3, 'KW' => 2, 'CA' => 2, 'MY' => 2, 'NG' => 2, 'ET' => 2, 'IN' => 2, 'TR' => 1, 'FR' => 1, 'OM' => 1, 'ZZ' => 4];

    private const array REFERRERS = ['' => 44, 'google.com' => 24, 'scholar.google.com' => 8, 'facebook.com' => 8, 'uofk.edu' => 4, 'linkedin.com' => 3, 'x.com' => 3, 'bing.com' => 2, 'wikipedia.org' => 2, 'duckduckgo.com' => 2];

    private const array DEVICES = ['mobile' => 58, 'desktop' => 34, 'tablet' => 8];

    private const array BROWSERS = [
        'mobile' => ['Chrome' => 55, 'Safari' => 35, 'Samsung Internet' => 7, 'Firefox' => 3],
        'desktop' => ['Chrome' => 60, 'Edge' => 15, 'Safari' => 15, 'Firefox' => 10],
        'tablet' => ['Safari' => 60, 'Chrome' => 40],
    ];

    /** Seconds of reading per view, by section: [shortest, longest]. */
    private const array READING = [
        Analytics::SECTION_HOME => [15, 90], Sections::ABOUT => [60, 260], Sections::CV => [45, 200], Sections::PUBLICATIONS => [30, 160],
        Sections::AWARDS => [20, 90], Sections::VIDEOS => [40, 420], Sections::PICTURES => [25, 150], Sections::TEACHING => [30, 140],
        Sections::COMMENTARY => [90, 480], Sections::COMMUNITY => [20, 80], Sections::OTHER => [30, 140], Sections::TRIBUTES => [60, 300],
    ];

    private Generator $faker;

    /**
     * Run the database seeds.
     */
    public function run(AnalyticsEventRepositoryInterface $events, SitemapService $sitemap): void
    {
        if (! app()->environment(Environments::developer())) {
            $this->command?->warn('AnalyticsEventSeeder only runs in the local environment.');

            return;
        }

        $count = $events->insertMany($this->generate($this->pages($sitemap), self::DAYS));
        $this->command?->info(sprintf('Seeded %s demo analytics page views.', number_format($count)));
    }

    /**
     * @param  array<string, string>  $pages  path => title
     * @return list<array<string, mixed>>
     */
    public function generate(array $pages, int $days, ?CarbonImmutable $now = null): array
    {
        $this->faker = fake();
        $this->faker->seed(2026);
        $now ??= CarbonImmutable::parse(Carbon::now()->toDateTimeString());
        $paths = array_keys($pages);
        $landing = $this->landingWeights($paths);
        $rows = [];

        for ($offset = $days; $offset >= 0; $offset--) {
            $day = $now->startOfDay()->subDays($offset);
            $trend = 38 + (self::DAYS - min($offset, self::DAYS)) * 0.4;
            $weekday = in_array($day->dayOfWeek, [5, 6], true) ? 0.72 : 1.0;
            $spike = $offset === 21 ? 2.6 : ($offset === 20 ? 1.6 : 1.0);
            $visitors = (int) round($trend * $weekday * $spike * $this->faker->randomFloat(2, 0.8, 1.2));

            if ($offset === 0) {
                $visitors = (int) round($visitors * max(0.05, ($now->hour * 60 + $now->minute) / 1440));
            }

            for ($v = 0; $v < $visitors; $v++) {
                $hash = hash('sha256', $day->toDateString().'#'.$v.'#'.$this->faker->uuid());
                $country = $this->pick(self::COUNTRIES);
                $device = $this->pick(self::DEVICES);
                $browser = $this->pick(self::BROWSERS[$device]);
                $visits = $this->faker->boolean(12) ? 2 : 1;
                $start = $day->addMinutes($this->faker->numberBetween(5 * 60, 22 * 60));

                for ($visit = 0; $visit < $visits; $visit++) {
                    $at = $start->addMinutes($visit * $this->faker->numberBetween(60, 240));
                    $referrer = $this->pick(self::REFERRERS);
                    $utm = $this->faker->boolean(6) ? 'newsletter' : ($referrer === 'facebook.com' && $this->faker->boolean(40) ? 'facebook' : null);
                    $path = $offset === 21 && $this->faker->boolean(55) ? '/tributes' : $this->pick($landing);
                    $views = min(6, 1 + (int) floor(-log(1 - $this->faker->randomFloat(4, 0, 0.98)) * 1.3));

                    for ($view = 0; $view < $views; $view++) {
                        if ($at->greaterThan($now)) {
                            break;
                        }

                        $section = Analytics::sectionFor($path);
                        [$shortest, $longest] = self::READING[$section] ?? [20, 120];
                        $bounce = $this->faker->boolean(18);
                        $engaged = $bounce ? $this->faker->numberBetween(0, 6) : $this->faker->numberBetween($shortest, $longest);
                        $depth = $bounce ? $this->faker->randomElement([0, 25]) : Analytics::SCROLL_DEPTHS[min(3, (int) floor($engaged / max(1, $longest) * 4.4))];

                        $rows[] = [
                            'view_id' => (string) Str::uuid(),
                            'visitor_hash' => $hash,
                            'occurred_at' => $at->toDateTimeString(),
                            'path' => $path,
                            'title' => $pages[$path],
                            'section' => $section,
                            'referrer_domain' => $view === 0 && $referrer !== '' ? $referrer : null,
                            'country' => $country,
                            'device' => DeviceType::from($device)->value,
                            'browser' => $browser,
                            'utm_source' => $view === 0 ? $utm : null,
                            'engaged_seconds' => $engaged,
                            'scroll_depth' => $depth,
                        ];

                        $at = $at->addSeconds($engaged + $this->faker->numberBetween(5, 40));
                        $path = $this->faker->randomElement($paths);
                    }
                }
            }
        }

        return $rows;
    }

    /**
     * The public pages of the site (from the sitemap, plus home): path => title as a browser would show it.
     *
     * @return array<string, string>
     */
    public function pages(SitemapService $sitemap): array
    {
        $pages = ['/' => 'Home'];
        $sections = Sections::definitions();

        foreach ($sitemap->entries() as $entry) {
            $path = (string) parse_url($entry['loc'], PHP_URL_PATH);

            if ($path === '' || Analytics::isExcludedPath($path)) {
                continue;
            }

            $section = Analytics::sectionFor($path);
            $slug = Str::afterLast($path, '/');
            $pages[$path] = isset($sections[$section]) && $sections[$section]['path'] === $path
                ? $sections[$section]['title']
                : Str::ucfirst(trim(preg_replace('/-[a-z0-9]{5}$/', '', str_replace(['placeholder-', '-'], ['', ' '], $slug)) ?? $slug));
        }

        return $pages;
    }

    /**
     * Where visits begin: the home page most, then the sections people are sent to.
     *
     * @param  list<string>  $paths
     * @return array<string, int>
     */
    private function landingWeights(array $paths): array
    {
        $weights = [];

        foreach ($paths as $path) {
            $weights[$path] = match (Analytics::sectionFor($path)) {
                Analytics::SECTION_HOME => 30,
                Sections::ABOUT => 12,
                Sections::PUBLICATIONS => 10,
                Sections::TRIBUTES => 8,
                Sections::COMMENTARY => $path === '/commentary' ? 5 : 4,
                default => 2,
            };
        }

        return $weights;
    }

    /**
     * @param  array<string, int>  $weights
     */
    private function pick(array $weights): string
    {
        $roll = $this->faker->numberBetween(1, array_sum($weights));

        foreach ($weights as $value => $weight) {
            $roll -= $weight;

            if ($roll <= 0) {
                return (string) $value;
            }
        }

        return (string) array_key_first($weights);
    }
}
