<?php

namespace Tests\Unit\Support;

use App\Constants\Analytics;
use App\Constants\DeviceType;
use App\Support\Countries;
use App\Support\Duration;
use App\Support\ReferrerDomain;
use App\Support\UserAgent;
use App\Support\WorldMap;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AnalyticsSupportTest extends TestCase
{
    public const string IPHONE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1';

    public const string IPAD = 'Mozilla/5.0 (iPad; CPU OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1';

    public const string ANDROID_TABLET = 'Mozilla/5.0 (Linux; Android 14; SM-X710) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36';

    public const string CHROME_DESKTOP = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36';

    public const string EDGE = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36 Edg/130.0.0.0';

    public const string FIREFOX = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:131.0) Gecko/20100101 Firefox/131.0';

    public const string SAMSUNG = 'Mozilla/5.0 (Linux; Android 14; SM-S918B) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/26.0 Chrome/122.0.0.0 Mobile Safari/537.36';

    #[Test]
    public function bots_crawlers_tools_and_lighthouse_are_recognised(): void
    {
        foreach ([
            'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
            'Mozilla/5.0 (Linux; Android 11; moto g power (2022)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36 Chrome-Lighthouse',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/130.0.0.0 Safari/537.36',
            'curl/8.4.0', 'python-requests/2.31', '', 'facebookexternalhit/1.1',
        ] as $agent) {
            $this->assertTrue(UserAgent::isBot($agent), $agent);
        }

        foreach ([self::IPHONE, self::CHROME_DESKTOP, self::EDGE, self::FIREFOX, self::SAMSUNG] as $agent) {
            $this->assertFalse(UserAgent::isBot($agent), $agent);
        }
    }

    #[Test]
    public function devices_and_browser_families_are_told_from_the_user_agent(): void
    {
        $this->assertSame(DeviceType::Mobile, UserAgent::device(self::IPHONE));
        $this->assertSame(DeviceType::Mobile, UserAgent::device(self::SAMSUNG));
        $this->assertSame(DeviceType::Tablet, UserAgent::device(self::IPAD));
        $this->assertSame(DeviceType::Tablet, UserAgent::device(self::ANDROID_TABLET));
        $this->assertSame(DeviceType::Desktop, UserAgent::device(self::CHROME_DESKTOP));

        $this->assertSame('Safari', UserAgent::browser(self::IPHONE));
        $this->assertSame('Chrome', UserAgent::browser(self::CHROME_DESKTOP));
        $this->assertSame('Edge', UserAgent::browser(self::EDGE));
        $this->assertSame('Firefox', UserAgent::browser(self::FIREFOX));
        $this->assertSame('Samsung Internet', UserAgent::browser(self::SAMSUNG));
        $this->assertSame(UserAgent::OTHER_BROWSER, UserAgent::browser('Mozilla/5.0 (Something)'));
    }

    #[Test]
    public function referrers_are_cut_to_their_domain_and_internal_or_odd_ones_are_dropped(): void
    {
        $this->assertSame('google.com', ReferrerDomain::from('https://www.google.com/search?q=khidir'));
        $this->assertSame('scholar.google.com', ReferrerDomain::from('https://user:pass@Scholar.Google.com/citations?x=1'));
        $this->assertNull(ReferrerDomain::from('https://profmokhidir.test/about', 'profmokhidir.test'));
        $this->assertNull(ReferrerDomain::from('https://www.profmokhidir.com/', 'profmokhidir.com'));
        $this->assertNull(ReferrerDomain::from('android-app://com.google.android.gm'));
        $this->assertNull(ReferrerDomain::from(''));
        $this->assertNull(ReferrerDomain::from('not a url'));
    }

    #[Test]
    public function countries_have_names_and_flags_and_odd_codes_are_unknown(): void
    {
        $this->assertSame('Sudan', Countries::name('sd'));
        $this->assertSame("\u{1F1F8}\u{1F1E9}", Countries::flag('SD'));
        $this->assertSame(Analytics::UNKNOWN_COUNTRY, Countries::normalise('XX'));
        $this->assertSame(Analytics::UNKNOWN_COUNTRY, Countries::normalise('T1'));
        $this->assertSame(Analytics::UNKNOWN_COUNTRY, Countries::normalise('Sudan'));
        $this->assertSame(Analytics::UNKNOWN_LABEL, Countries::name(Analytics::UNKNOWN_COUNTRY));
        $this->assertSame('', Countries::flag(Analytics::UNKNOWN_COUNTRY));
    }

    #[Test]
    public function the_world_map_has_every_country_inline_and_tiers_on_a_root_scale(): void
    {
        $map = WorldMap::data();

        $this->assertSame('0 0 1010 666', $map['viewBox']);
        $this->assertGreaterThan(200, count($map['countries']));
        $this->assertArrayHasKey('SD', $map['countries']);
        $this->assertStringStartsWith('M', $map['countries']['SD']['d']);
        $this->assertStringNotContainsString('http', json_encode($map['countries']));

        $this->assertSame(0, WorldMap::tier(0, 100));
        $this->assertSame(1, WorldMap::tier(1, 100));
        $this->assertSame(3, WorldMap::tier(25, 100));
        $this->assertSame(WorldMap::TIERS, WorldMap::tier(100, 100));
    }

    #[Test]
    public function durations_read_shortly_and_have_a_machine_form(): void
    {
        $this->assertSame('45s', Duration::short(45));
        $this->assertSame('2m 05s', Duration::short(125));
        $this->assertSame('3h 12m', Duration::short(3 * 3600 + 12 * 60 + 9));
        $this->assertSame('PT2M5S', Duration::iso(125));
        $this->assertSame('PT0S', Duration::iso(0));
    }
}
