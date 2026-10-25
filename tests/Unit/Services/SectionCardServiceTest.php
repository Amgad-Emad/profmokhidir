<?php

namespace Tests\Unit\Services;

use App\Constants\Disks;
use App\Constants\Pages;
use App\Constants\SectionCards;
use App\Constants\Sections;
use App\Models\Album;
use App\Services\AlbumService;
use App\Services\PageService;
use App\Services\SectionCardService;
use App\Support\PlaceholderImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SectionCardServiceTest extends TestCase
{
    use RefreshDatabase;

    private SectionCardService $cards;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
        $this->cards = $this->app->make(SectionCardService::class);
    }

    #[Test]
    public function each_card_carries_the_chapter_numeral_heading_intro_and_address_of_its_section(): void
    {
        $cards = $this->cards->cards(Sections::aboutLinks());

        $this->assertSame([Sections::CV, Sections::PUBLICATIONS, Sections::AWARDS], array_column($cards, 'key'));
        $this->assertSame(['II', 'III', 'IV'], array_column($cards, 'numeral'));
        $this->assertSame(['CV and Achievements', 'Publications', 'Awards'], array_column($cards, 'title'));
        $this->assertSame(Sections::definitions()[Sections::CV]['summary'], $cards[0]['summary']);
        $this->assertSame([route('cv'), route('publications'), route('awards')], array_column($cards, 'url'));
        $this->assertSame([null, null, null], array_column($cards, 'image'));
    }

    #[Test]
    public function a_caller_may_give_its_own_line_and_image_for_a_card(): void
    {
        $photo = $this->app->make(AlbumService::class)->addPhoto(Album::factory()->published()->create(), PlaceholderImage::grey(64, 48), []);

        $cards = $this->cards->cards([Sections::AWARDS, Sections::TRIBUTES], [Sections::AWARDS => 'His prizes.', Sections::TRIBUTES => ''], [Sections::TRIBUTES => $photo]);

        $this->assertSame('His prizes.', $cards[0]['summary']);
        $this->assertSame(Sections::definitions()[Sections::TRIBUTES]['summary'], $cards[1]['summary'], 'an empty line falls back to the intro');
        $this->assertSame('XI', $cards[1]['numeral']);
        $this->assertSame($photo->getKey(), $cards[1]['image']?->getKey());
    }

    #[Test]
    public function the_card_image_set_on_a_section_page_in_admin_pages_shows_on_its_card(): void
    {
        $pages = $this->app->make(PageService::class);
        $pages->install();
        $cv = $pages->findByKey(Pages::CV);

        $media = $pages->uploadImage($cv, SectionCards::IMAGE_BLOCK, UploadedFile::fake()->image('desk.jpg', 900, 300));
        $pages->publish($cv, [SectionCards::IMAGE_BLOCK => ['media_id' => $media->getKey(), 'alt_text' => 'His desk', 'caption' => '', 'place' => 'Khartoum', 'year' => 1980]]);

        $this->assertSame($media->getKey(), $this->cards->card(Sections::CV)['image']?->getKey());
        $this->assertNull($this->cards->image(Sections::TRIBUTES), 'the tributes page has no card image block');
    }
}
