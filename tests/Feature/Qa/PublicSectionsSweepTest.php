<?php

namespace Tests\Feature\Qa;

use App\Constants\CommunityCopy;
use App\Constants\Disks;
use App\Constants\Sections;
use App\Constants\TributeStatus;
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
 * Client-review sweep: every public section renders the seeded placeholder
 * dataset, and every section shows its designed empty state when nothing is
 * published. Each page's own test covers its details; this one proves the
 * whole site in one pass.
 */
class PublicSectionsSweepTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
        Storage::fake(Disks::PRIVATE);
    }

    #[Test]
    public function every_public_section_renders_the_seeded_content(): void
    {
        $this->seed();

        $expected = [
            Sections::ABOUT => Profile::query()->published()->firstOrFail()->name,
            Sections::CV => CvEntry::query()->published()->firstOrFail()->title,
            Sections::PUBLICATIONS => Publication::query()->published()->firstOrFail()->title,
            Sections::AWARDS => Award::query()->published()->firstOrFail()->name,
            Sections::VIDEOS => Video::query()->published()->firstOrFail()->title,
            Sections::PICTURES => Album::query()->published()->firstOrFail()->title,
            Sections::TEACHING => TeachingMaterial::query()->published()->firstOrFail()->title,
            Sections::COMMENTARY => Commentary::query()->published()->firstOrFail()->title,
            Sections::COMMUNITY => CommunityCopy::HEADING,
            Sections::OTHER => OtherItem::query()->published()->firstOrFail()->title,
            Sections::TRIBUTES => Tribute::query()->withStatus(TributeStatus::Approved)->firstOrFail()->name,
        ];

        foreach (Sections::definitions() as $key => $section) {
            $response = $this->get($section['path'])->assertOk();

            $response
                ->assertSee('<title>'.e($section['title']).' · ', false)
                ->assertSee($expected[$key])
                ->assertDontSee('livewire', false);

            if ($key !== Sections::COMMUNITY) {
                $response->assertDontSee('class="empty-state', false);
            }
        }

        $this->get(route('home'))->assertOk()->assertSee($expected[Sections::ABOUT])->assertDontSee('class="empty-state', false);
    }

    #[Test]
    public function every_seeded_detail_page_renders(): void
    {
        $this->seed();

        $video = Video::query()->published()->firstOrFail();
        $album = Album::query()->published()->firstOrFail();
        $commentary = Commentary::query()->published()->firstOrFail();
        $other = OtherItem::query()->published()->get()->first(fn (OtherItem $item): bool => $item->hasDetail());

        $this->get(route('videos.show', $video->slug))->assertOk()->assertSee($video->title);
        $this->get(route('pictures.show', $album->slug))->assertOk()->assertSee($album->title);
        $this->get(route('commentary.show', $commentary->slug))->assertOk()->assertSee($commentary->title);

        if ($other !== null) {
            $this->get(route('other.show', $other->slug))->assertOk()->assertSee($other->title);
        }

        $this->get(route('sitemap'))->assertOk()
            ->assertSee(route('videos.show', $video->slug))
            ->assertSee(route('pictures.show', $album->slug))
            ->assertSee(route('commentary.show', $commentary->slug));
    }

    #[Test]
    public function every_public_section_shows_a_designed_empty_state_when_nothing_is_published(): void
    {
        foreach (Sections::definitions() as $key => $section) {
            $response = $this->get($section['path'])->assertOk()->assertSee('<title>'.e($section['title']).' · ', false);

            if ($key === Sections::COMMUNITY) {
                $response->assertSee(CommunityCopy::APPLY_HEADING);

                continue;
            }

            $response
                ->assertSee('class="empty-state', false)
                ->assertSee('rule-brass-short', false);
        }

        $home = $this->get(route('home'))->assertOk();

        $this->assertSame(2, substr_count($home->getContent(), 'class="empty-state flex'), 'The timeline and the figures each have an empty state');
    }
}
