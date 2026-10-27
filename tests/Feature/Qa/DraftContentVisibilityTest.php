<?php

namespace Tests\Feature\Qa;

use App\Constants\Disks;
use App\Constants\Sections;
use App\Models\Album;
use App\Models\Award;
use App\Models\Commentary;
use App\Models\CvEntry;
use App\Models\OtherItem;
use App\Models\Profile;
use App\Models\Publication;
use App\Models\TeachingMaterial;
use App\Models\Tribute;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Draft content of every type is created with a marker string, then every
 * public page (lists, detail pages, home, sitemap) is checked for the marker.
 */
class DraftContentVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private const string DRAFT = 'Zqdraftmarker';

    private const string LIVE = 'Zqlivemarker';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
        Storage::fake(Disks::PRIVATE);
    }

    /**
     * @return list<string>
     */
    private function publicPaths(): array
    {
        return ['/', ...array_column(Sections::definitions(), 'path'), '/tributes/new', '/sitemap.xml', '/sitemaps/en.xml', '/sitemaps/ar.xml'];
    }

    #[Test]
    public function draft_content_never_appears_on_any_public_page_or_in_the_sitemap(): void
    {
        Profile::factory()->draft()->create(['name' => self::DRAFT.' profile', 'short_bio' => self::DRAFT.' bio']);
        CvEntry::factory()->draft()->create(['title' => self::DRAFT.' cv entry']);
        Publication::factory()->draft()->create(['title' => self::DRAFT.' publication']);
        Award::factory()->draft()->create(['name' => self::DRAFT.' award']);
        Video::factory()->draft()->create(['title' => self::DRAFT.' video', 'slug' => 'zq-draft-video']);
        Album::factory()->draft()->create(['title' => self::DRAFT.' album', 'slug' => 'zq-draft-album']);
        TeachingMaterial::factory()->draft()->create(['title' => self::DRAFT.' material']);
        Commentary::factory()->draft()->create(['title' => self::DRAFT.' commentary', 'slug' => 'zq-draft-commentary']);
        OtherItem::factory()->draft()->create(['title' => self::DRAFT.' item', 'slug' => 'zq-draft-item', 'body' => '<p>Body</p>', 'destination_url' => null]);
        Tribute::factory()->pending()->create(['name' => self::DRAFT.' pending tribute']);
        Tribute::factory()->rejected()->create(['name' => self::DRAFT.' rejected tribute']);

        foreach ($this->publicPaths() as $path) {
            $this->get($path)->assertOk()->assertDontSee(self::DRAFT);
        }

        $this->get(route('videos.show', 'zq-draft-video'))->assertNotFound();
        $this->get(route('pictures.show', 'zq-draft-album'))->assertNotFound();
        $this->get(route('commentary.show', 'zq-draft-commentary'))->assertNotFound();
        $this->get(route('other.show', 'zq-draft-item'))->assertNotFound();
    }

    #[Test]
    public function the_same_content_appears_once_it_is_published(): void
    {
        Profile::factory()->published()->create(['name' => self::LIVE.' profile']);
        CvEntry::factory()->published()->create(['title' => self::LIVE.' cv entry']);
        Publication::factory()->published()->create(['title' => self::LIVE.' publication']);
        Award::factory()->published()->create(['name' => self::LIVE.' award']);
        Video::factory()->published()->create(['title' => self::LIVE.' video', 'slug' => 'zq-live-video']);
        Album::factory()->published()->create(['title' => self::LIVE.' album', 'slug' => 'zq-live-album']);
        TeachingMaterial::factory()->published()->create(['title' => self::LIVE.' material']);
        Commentary::factory()->published()->create(['title' => self::LIVE.' commentary', 'slug' => 'zq-live-commentary']);
        OtherItem::factory()->published()->create(['title' => self::LIVE.' item', 'slug' => 'zq-live-item', 'body' => '<p>Body</p>', 'destination_url' => null]);
        Tribute::factory()->approved()->create(['name' => self::LIVE.' tribute']);

        $sections = Sections::definitions();

        foreach ([
            Sections::ABOUT, Sections::CV, Sections::PUBLICATIONS, Sections::AWARDS, Sections::VIDEOS, Sections::PICTURES,
            Sections::TEACHING, Sections::COMMENTARY, Sections::OTHER, Sections::TRIBUTES,
        ] as $key) {
            $this->get($sections[$key]['path'])->assertOk()->assertSee(self::LIVE);
        }

        $this->get(route('videos.show', 'zq-live-video'))->assertOk()->assertSee(self::LIVE);
        $this->get(route('pictures.show', 'zq-live-album'))->assertOk()->assertSee(self::LIVE);
        $this->get(route('commentary.show', 'zq-live-commentary'))->assertOk()->assertSee(self::LIVE);
        $this->get(route('other.show', 'zq-live-item'))->assertOk()->assertSee(self::LIVE);

        $this->get('/sitemaps/en.xml')->assertOk()
            ->assertSee(route('videos.show', 'zq-live-video'))
            ->assertSee(route('commentary.show', 'zq-live-commentary'));
    }
}
