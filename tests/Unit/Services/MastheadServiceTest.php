<?php

namespace Tests\Unit\Services;

use App\Constants\Chapters;
use App\Constants\Disks;
use App\Constants\Masthead;
use App\Constants\Sections;
use App\Models\Album;
use App\Models\Profile;
use App\Models\Publication;
use App\Models\Tribute;
use App\Models\User;
use App\Services\MastheadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MastheadServiceTest extends TestCase
{
    use RefreshDatabase;

    private MastheadService $masthead;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
        $this->masthead = $this->app->make(MastheadService::class);
    }

    #[Test]
    public function it_builds_five_numbered_chapters_with_their_section_links(): void
    {
        $chapters = $this->masthead->chapters();

        $this->assertSame(Chapters::all(), array_column($chapters, 'key'));
        $this->assertSame(['I', 'II', 'III', 'IV', 'V'], array_column($chapters, 'numeral'));
        $this->assertSame(Masthead::chapterPanelId(Chapters::LIFE), $chapters[0]['panelId']);
        $this->assertSame(Masthead::chapterButtonId(Chapters::LIFE), $chapters[0]['buttonId']);
        $this->assertSame('The man, his path and his honours.', $chapters[0]['strap']);

        $life = $chapters[0]['items'];
        $this->assertSame(['About', 'CV & Achievements', 'Awards', 'Other Information'], array_column($life, 'label'));
        $this->assertSame(route('about'), $life[0]['url']);
        $this->assertSame(Chapters::descriptions()[Sections::ABOUT], $life[0]['description']);
        $this->assertFalse($chapters[0]['active']);
    }

    #[Test]
    public function the_current_route_marks_its_chapter_and_link_active(): void
    {
        $chapters = $this->masthead->chapters('publications');
        $work = $chapters[1];

        $this->assertTrue($work['active']);
        $this->assertSame([true, false, false], array_column($work['items'], 'active'));
        $this->assertSame([false, true, false, false, false], array_column($chapters, 'active'));

        $this->assertSame(Chapters::LIFE, $this->masthead->activeChapter('cv'));
        $this->assertSame(Chapters::ARCHIVE, $this->masthead->activeChapter('pictures.show'));
        $this->assertNull($this->masthead->activeChapter('home'));
        $this->assertNull($this->masthead->activeChapter(null));
    }

    #[Test]
    public function guests_see_the_sign_in_and_members_the_chat(): void
    {
        $community = fn (?User $user): array => array_column($this->masthead->chapters(null, $user)[3]['items'], 'label');
        $tributes = fn (?User $user): array => array_column($this->masthead->chapters(null, $user)[4]['items'], 'label');

        $this->assertSame(['Community', Masthead::SIGN_IN], $community(null));
        $this->assertSame(['Community', Masthead::OPEN_CHAT], $community(User::factory()->member()->create()));
        $this->assertSame(['Community', Masthead::OPEN_CHAT], $community(User::factory()->admin()->create()));
        $this->assertSame(['Community'], $community(User::factory()->applicant()->create()));
        $this->assertSame(['Tributes', Masthead::LEAVE_TRIBUTE], $tributes(null));

        $this->assertSame(['label' => Masthead::SIGN_IN, 'url' => route('login')], $this->masthead->utility(null)['link']);
        $this->assertSame(['label' => Masthead::OPEN_CHAT, 'url' => route('community.chat')], $this->masthead->utility(User::factory()->member()->create())['link']);
        $this->assertSame(['label' => Masthead::ACCOUNT, 'url' => route('account.show')], $this->masthead->utility(User::factory()->editor()->create())['link']);
        $this->assertSame(Masthead::UTILITY_LINE, $this->masthead->utility(null)['line']);
    }

    #[Test]
    public function featured_items_are_null_until_something_is_published(): void
    {
        foreach (Chapters::featuredKinds() as $kind) {
            $this->assertNull($this->masthead->featured($kind), $kind);
        }

        $this->assertNull($this->masthead->featured('nope'));
        $this->assertNull($this->masthead->chapters()[3]['featured'], 'Community has no featured item');
    }

    #[Test]
    public function the_latest_publication_is_featured_in_work(): void
    {
        Publication::factory()->published()->year(1998)->create(['title' => 'Older']);
        $latest = Publication::factory()->published()->year(2015)->create(['title' => 'Newer']);
        Publication::factory()->draft()->year(2024)->create(['title' => 'Draft']);

        $featured = $this->masthead->chapters()[1]['featured'];

        $this->assertSame(Chapters::FEATURED_PUBLICATION, $featured['kind']);
        $this->assertSame('Latest publication', $featured['label']);
        $this->assertSame('Newer', $featured['title']);
        $this->assertStringContainsString('2015', $featured['meta']);
        $this->assertSame(route('publications').'#publication-'.$latest->slug, $featured['url']);
        $this->assertNull($featured['media']);
    }

    #[Test]
    public function the_newest_album_is_featured_in_archive_with_its_cover(): void
    {
        Album::factory()->published()->withPhotos(1, 64, 64)->create(['title' => 'Older', 'published_at' => now()->subYear()]);
        $newest = Album::factory()->published()->withPhotos(2, 64, 64)->create(['title' => 'Newest', 'published_at' => now()->subDay()]);
        Album::factory()->draft()->create(['title' => 'Draft']);

        $featured = $this->masthead->chapters()[2]['featured'];

        $this->assertSame('Newest', $featured['title']);
        $this->assertSame('2 photographs', $featured['meta']);
        $this->assertSame(route('pictures.show', $newest), $featured['url']);
        $this->assertTrue($featured['media']->is($newest->cover()));
    }

    #[Test]
    public function the_featured_tribute_and_the_portrait_are_featured(): void
    {
        Tribute::factory()->approved()->create(['name' => 'Someone else']);
        Tribute::factory()->featured()->create(['name' => 'Ahmed', 'message' => str_repeat('A long word ', 30)]);
        Profile::factory()->published()->withPortrait()->create(['name' => 'Mohamed Osman Khidir', 'title' => 'Professor of Agronomy']);

        $chapters = $this->masthead->chapters();

        $tribute = $chapters[4]['featured'];
        $this->assertSame('Ahmed', $tribute['title']);
        $this->assertSame(MastheadService::QUOTE_LENGTH, mb_strlen($tribute['quote']));
        $this->assertStringEndsWith('…', $tribute['quote']);
        $this->assertSame(route('tributes'), $tribute['url']);

        $portrait = $chapters[0]['featured'];
        $this->assertSame('In honour of', $portrait['label']);
        $this->assertSame('Mohamed Osman Khidir', $portrait['title']);
        $this->assertSame('Professor of Agronomy', $portrait['meta']);
        $this->assertNotNull($portrait['media']);
        $this->assertSame(route('about'), $portrait['url']);
    }
}
