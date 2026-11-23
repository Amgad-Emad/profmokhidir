<?php

namespace Tests\Feature\Cache;

use App\Constants\CacheKeys;
use App\Constants\CacheSerializable;
use App\Constants\Disks;
use App\Models\Award;
use App\Models\CvEntry;
use App\Models\HomeQuote;
use App\Models\Profile;
use App\Models\Publication;
use App\Services\HomePageService;
use App\Services\HomeQuoteService;
use App\Services\ProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Laravel's cache stores refuse to unserialise objects unless they are listed in
 * cache.serializable_classes. The public page services cache Eloquent models, so
 * this suite runs them against the real database store rather than the array
 * store the rest of the suite uses.
 */
class CacheSerializableClassesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
        config(['cache.default' => 'database']);
        Cache::store('database')->flush();
    }

    #[Test]
    public function the_config_uses_the_allow_list_and_every_cached_model_is_in_it(): void
    {
        $this->assertSame(CacheSerializable::classes(), config('cache.serializable_classes'));

        foreach (CacheSerializable::classes() as $class) {
            $this->assertTrue(class_exists($class) || enum_exists($class), "{$class} does not exist");
        }

        foreach (CacheSerializable::models() as $model) {
            $this->assertTrue(method_exists($model, 'scopePublished') || (new $model)->hasNamedScope('published'), "{$model} has no published scope");
        }
    }

    #[Test]
    public function the_published_profile_with_its_portrait_survives_a_round_trip_through_the_database_store(): void
    {
        $profile = Profile::factory()->published()->withPortrait()->create();
        $service = $this->app->make(ProfileService::class);

        $service->get();
        $this->assertTrue(Cache::store('database')->has(CacheKeys::PROFILE));

        $cached = $this->app->make(ProfileService::class)->get();

        $this->assertInstanceOf(Profile::class, $cached);
        $this->assertTrue($cached->is($profile));
        $this->assertNotNull($cached->portrait());
        $this->assertTrue($cached->portrait()->hasGeneratedConversion('w480'));
    }

    #[Test]
    public function the_home_page_payload_survives_a_round_trip_through_the_database_store(): void
    {
        Profile::factory()->published()->withPortrait()->create();
        Publication::factory()->published()->count(2)->create();
        CvEntry::factory()->published()->count(2)->create(['start_year' => 1965]);
        Award::factory()->published()->create(['year' => 1985]);
        HomeQuote::factory()->published()->create(['quote' => 'Cached quote']);

        // The home sections are the page's own content now (filled from the records, as the migration did).
        $this->fillHomeSections();
        $this->app->make(HomePageService::class)->data();
        $data = $this->app->make(HomePageService::class)->data();

        $this->assertInstanceOf(Profile::class, $data['profile']);
        $this->assertSame(['1980–1989'], array_column($data['chapters'], 'years'));
        $this->assertSame('2', $data['stats'][0]['value']);
        $this->assertContainsOnlyInstancesOf(HomeQuote::class, $this->app->make(HomeQuoteService::class)->published());
    }

    #[Test]
    public function the_about_page_renders_from_the_database_cache_on_a_second_request(): void
    {
        Profile::factory()->published()->withPortrait()->create(['name' => 'Cached Name']);

        $this->get(route('about'))->assertOk()->assertSee('Cached Name');
        $this->assertTrue(Cache::store('database')->has(CacheKeys::PROFILE));
        $this->get(route('about'))->assertOk()->assertSee('Cached Name');
        $this->get(route('home'))->assertOk()->assertSee('Khartoum, 1998');
    }
}
