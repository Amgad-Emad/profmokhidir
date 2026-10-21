<?php

namespace Tests\Feature\Pages;

use App\Constants\Disks;
use App\Constants\TributeCards;
use App\Models\Tribute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TributeShareCardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
    }

    #[Test]
    public function an_approved_tribute_serves_its_card_as_a_square_png_drawn_once(): void
    {
        $tribute = Tribute::factory()->approved()->create(['message' => 'He taught me to look twice.']);

        $response = $this->get(route('tributes.card', $tribute))->assertOk();

        $this->assertSame('image/png', $response->headers->get('Content-Type'));
        $this->assertSame('inline; filename="tribute-'.$tribute->id.'.png"', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('max-age=86400', (string) $response->headers->get('Cache-Control'));
        $this->assertSame(route(TributeCards::ROUTE, $tribute), route('tributes.card', $tribute));

        $path = Storage::disk(Disks::PUBLIC)->path(TributeCards::path($tribute->id));
        $this->assertFileExists($path);
        $this->assertSame([1080, 1080], array_slice(getimagesize($path), 0, 2));
        $this->assertSame(TributeCards::path($tribute->id), $tribute->fresh()->share_card_path);

        // Cached: the second request serves the same file.
        $before = filemtime($path);
        $this->get(route('tributes.card', $tribute))->assertOk();
        $this->assertSame($before, filemtime($path));
    }

    #[Test]
    public function pending_rejected_and_unknown_tributes_have_no_card(): void
    {
        $this->get(route('tributes.card', Tribute::factory()->pending()->create()))->assertNotFound();
        $this->get(route('tributes.card', Tribute::factory()->rejected()->create()))->assertNotFound();
        $this->get('/tributes/999999/card')->assertNotFound();
        $this->get('/tributes/abc/card')->assertNotFound();
        $this->assertSame([], Storage::disk(Disks::PUBLIC)->allFiles());
    }

    #[Test]
    public function every_tribute_in_the_book_links_to_its_card(): void
    {
        $first = Tribute::factory()->approved()->create(['name' => 'Zq First']);
        $featured = Tribute::factory()->featured()->create(['name' => 'Zq Featured']);

        $this->get(route('tributes'))
            ->assertOk()
            ->assertSee('href="'.route('tributes.card', $first).'" class="tribute-entry__card t-label" download="tribute-'.$first->id.'.png"', false)
            ->assertSee('href="'.route('tributes.card', $featured).'" class="tribute-entry__card t-label" download="tribute-'.$featured->id.'.png"', false);

        // Linking never draws: cards are drawn on request only.
        $this->assertSame([], Storage::disk(Disks::PUBLIC)->allFiles());
    }
}
