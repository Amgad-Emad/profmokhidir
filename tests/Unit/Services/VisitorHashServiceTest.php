<?php

namespace Tests\Unit\Services;

use App\Models\AnalyticsSalt;
use App\Services\VisitorHashService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VisitorHashServiceTest extends TestCase
{
    use RefreshDatabase;

    private const string IP = '203.0.113.42';

    private const string AGENT = 'Mozilla/5.0 (iPhone) Safari/604.1';

    #[Test]
    public function the_same_visitor_hashes_the_same_within_a_day_and_never_contains_the_ip_or_agent(): void
    {
        $service = $this->app->make(VisitorHashService::class);

        $hash = $service->hash(self::IP, self::AGENT);

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $hash);
        $this->assertSame($hash, $service->hash(self::IP, self::AGENT));
        $this->assertNotSame($hash, $service->hash('203.0.113.43', self::AGENT));
        $this->assertNotSame($hash, $service->hash(self::IP, self::AGENT.' x'));
        $this->assertStringNotContainsString(self::IP, $hash);
        $this->assertSame(1, AnalyticsSalt::query()->count());
    }

    #[Test]
    public function a_new_day_gets_a_new_salt_the_old_one_is_deleted_and_the_hash_changes(): void
    {
        Carbon::setTestNow('2026-10-23 23:59:00');
        $yesterday = $this->app->make(VisitorHashService::class)->hash(self::IP, self::AGENT);
        $oldSalt = AnalyticsSalt::query()->sole()->salt;

        Carbon::setTestNow('2026-10-24 00:01:00');
        $today = $this->app->make(VisitorHashService::class)->hash(self::IP, self::AGENT);

        $this->assertNotSame($yesterday, $today);
        $this->assertSame(['2026-10-24'], AnalyticsSalt::query()->get()->map(fn (AnalyticsSalt $salt): string => $salt->day->toDateString())->all());
        $this->assertNotSame($oldSalt, AnalyticsSalt::query()->sole()->salt);
        $this->assertDatabaseMissing('analytics_salts', ['salt' => $oldSalt]);

        Carbon::setTestNow();
    }

    #[Test]
    public function rotate_makes_todays_salt_and_forget_before_only_deletes(): void
    {
        $service = $this->app->make(VisitorHashService::class);
        AnalyticsSalt::factory()->forDay(now()->subDays(2))->create();
        AnalyticsSalt::factory()->yesterday()->create();

        $this->assertSame(2, $service->rotate(CarbonImmutable::now()));
        $this->assertTrue(AnalyticsSalt::query()->whereDate('day', now()->toDateString())->exists());

        $this->assertSame(0, $service->forgetBefore(now()));
        $this->assertSame(1, $service->forgetBefore(now()->addDay()));
        $this->assertSame(0, AnalyticsSalt::query()->count());
    }

    #[Test]
    public function the_site_id_defaults_to_the_app_host(): void
    {
        config(['app.url' => 'https://profmokhidir.com', 'services.analytics.site_id' => null]);
        $this->assertSame('profmokhidir.com', $this->app->make(VisitorHashService::class)->siteId());

        config(['services.analytics.site_id' => 'site-1']);
        $this->assertSame('site-1', $this->app->make(VisitorHashService::class)->siteId());
    }
}
