<?php

namespace Tests\Unit\Services;

use App\Constants\CacheKeys;
use App\Constants\ContentStatus;
use App\Constants\Disks;
use App\Constants\MediaCollections;
use App\Constants\MediaProperties;
use App\Constants\MediaTreatment;
use App\Models\Profile;
use App\Services\ProfileService;
use App\Support\PlaceholderImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProfileServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProfileService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
        $this->service = $this->app->make(ProfileService::class);
    }

    #[Test]
    public function save_creates_the_single_row_then_updates_it(): void
    {
        $this->assertNull($this->service->current());

        $profile = $this->service->save(['name' => 'Prof. K', 'title' => 'Professor']);
        $this->assertSame(1, Profile::query()->count());

        $updated = $this->service->save(['title' => 'Emeritus']);
        $this->assertSame(1, Profile::query()->count());
        $this->assertTrue($updated->is($profile));
        $this->assertSame('Emeritus', $updated->title);
        $this->assertSame('Prof. K', $updated->name);
    }

    #[Test]
    public function get_returns_only_a_published_profile_and_caches_it(): void
    {
        $profile = Profile::factory()->draft()->create();

        $this->assertNull($this->service->get());

        $this->service->update($profile, ['status' => ContentStatus::Published]);
        $this->assertTrue($this->service->get()->is($profile));
        $this->assertTrue(Cache::has(CacheKeys::PROFILE));

        $this->service->save(['title' => 'Changed']);
        $this->assertFalse(Cache::has(CacheKeys::PROFILE));
        $this->assertSame('Changed', $this->service->get()->title);
    }

    #[Test]
    public function set_portrait_attaches_a_single_portrait_with_the_portrait_treatment(): void
    {
        $profile = Profile::factory()->create();

        $first = $this->service->setPortrait($profile, PlaceholderImage::grey(64, 64), [MediaProperties::ALT_TEXT => 'One']);
        $this->assertSame(MediaCollections::PORTRAIT, $first->collection_name);
        $this->assertSame(MediaTreatment::Portrait->value, $first->getCustomProperty(MediaProperties::TREATMENT));

        $second = $this->service->setPortrait($profile, PlaceholderImage::grey(64, 64), [MediaProperties::ALT_TEXT => 'Two']);

        $this->assertSame(1, $profile->fresh()->media()->count());
        $this->assertTrue($profile->fresh()->portrait()->is($second));
        $this->assertDatabaseMissing('media', ['id' => $first->id]);
    }

    #[Test]
    public function remove_portrait_clears_the_collection(): void
    {
        $profile = Profile::factory()->withPortrait()->create();
        $this->assertNotNull($profile->portrait());

        $this->service->removePortrait($profile);

        $this->assertNull($profile->fresh()->portrait());
        $this->service->removePortrait($profile->fresh()); // no-op without a portrait
    }

    #[Test]
    public function prepare_normalises_contact_email_and_reindexes_lists(): void
    {
        $profile = $this->service->save([
            'name' => 'P',
            'contact_email_public' => '  Office@Example.COM ',
            'expertise' => [3 => 'Agronomy', 9 => 'Soils'],
            'key_facts' => [5 => ['label' => 'Born', 'value' => '1940']],
        ]);

        $this->assertSame('office@example.com', $profile->contact_email_public);
        $this->assertSame(['Agronomy', 'Soils'], $profile->expertise);
        $this->assertSame([['label' => 'Born', 'value' => '1940']], $profile->key_facts);

        $profile = $this->service->save(['contact_email_public' => '   ']);
        $this->assertNull($profile->contact_email_public);
    }
}
