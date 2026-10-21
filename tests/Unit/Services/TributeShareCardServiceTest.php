<?php

namespace Tests\Unit\Services;

use App\Constants\Disks;
use App\Constants\TributeCards;
use App\Constants\TributeRelationship;
use App\Constants\Typesetting;
use App\Models\Tribute;
use App\Services\TributeShareCardService;
use App\Support\BrandCanvas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TributeShareCardServiceTest extends TestCase
{
    use RefreshDatabase;

    private TributeShareCardService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);

        $this->service = $this->app->make(TributeShareCardService::class);
    }

    #[Test]
    public function only_approved_tributes_have_a_card(): void
    {
        $this->assertNull($this->service->pathFor(Tribute::factory()->pending()->create()));
        $this->assertNull($this->service->pathFor(Tribute::factory()->rejected()->create()));
        $this->assertSame([], Storage::disk(Disks::PUBLIC)->allFiles());
    }

    #[Test]
    public function the_card_is_drawn_once_at_the_instagram_size_and_its_path_remembered(): void
    {
        $tribute = Tribute::factory()->approved()->relationship(TributeRelationship::Student)->create([
            'name' => 'Ahmed El-Sheikh', 'location' => 'Khartoum, Sudan', 'message' => "He taught me to look twice before answering.\nAnd three times before writing it down.",
        ]);

        $path = $this->service->pathFor($tribute);

        $this->assertNotNull($path);
        $this->assertSame(TributeCards::path($tribute->id), $tribute->fresh()->share_card_path);
        $this->assertSame('tributes/share/tribute-'.$tribute->id.'.png', $tribute->fresh()->share_card_path);
        Storage::disk(Disks::PUBLIC)->assertExists(TributeCards::path($tribute->id));

        $size = getimagesize($path);
        $this->assertSame([1080, 1080, 'image/png'], [$size[0], $size[1], $size['mime']]);
        $this->assertSame([TributeCards::WIDTH, TributeCards::HEIGHT], [1080, 1080]);

        // A second request serves the cached file instead of drawing again.
        file_put_contents($path, 'marker');
        $this->assertSame($path, $this->service->pathFor($tribute->fresh()));
        $this->assertSame('marker', file_get_contents($path));

        // A missing file (cleared storage) is drawn afresh.
        unlink($path);
        $this->assertNotNull($this->service->pathFor($tribute->fresh()));
        $this->assertSame(1080, getimagesize($path)[0]);

        $this->assertSame('tribute-'.$tribute->id.'.png', $this->service->filename($tribute));
    }

    #[Test]
    public function forget_removes_the_file_and_the_remembered_path(): void
    {
        $tribute = Tribute::factory()->approved()->create();
        $path = $this->service->pathFor($tribute);

        $this->service->forget($tribute->fresh());

        $this->assertFileDoesNotExist($path);
        $this->assertNull($tribute->fresh()->share_card_path);

        // Forgetting a tribute without a card is harmless.
        $this->service->forget(Tribute::factory()->pending()->create());
    }

    #[Test]
    public function generate_missing_draws_every_approved_tribute_without_a_card(): void
    {
        $withCard = Tribute::factory()->approved()->create();
        $this->service->generate($withCard);
        Tribute::factory()->approved()->count(2)->create();
        Tribute::factory()->pending()->create();

        $this->assertSame(2, $this->service->generateMissing());
        $this->assertSame(0, $this->service->generateMissing());
        $this->assertCount(3, Storage::disk(Disks::PUBLIC)->allFiles());
        $this->assertSame(0, Tribute::query()->published()->whereNull('share_card_path')->count());
    }

    #[Test]
    public function long_tributes_are_excerpted_at_a_word_boundary_with_an_ellipsis(): void
    {
        $short = 'He said "look twice" -- and we did.';
        $this->assertSame('He said '.Typesetting::LEFT_DOUBLE_QUOTE.'look twice'.Typesetting::RIGHT_DOUBLE_QUOTE.' '.Typesetting::EN_DASH.' and we did.', $this->service->excerpt($short));

        $long = str_repeat('A patient teacher who waited for the right question. ', 20);
        $excerpt = $this->service->excerpt($long);

        $this->assertLessThanOrEqual(TributeCards::EXCERPT_MAX_CHARS + 1, mb_strlen($excerpt));
        $this->assertStringEndsWith(Typesetting::ELLIPSIS, $excerpt);
        $kept = mb_substr($excerpt, 0, -1);
        $this->assertStringStartsWith($kept, $long, 'The excerpt is a prefix of the text');
        $this->assertMatchesRegularExpression('/\p{L}$/u', $kept, 'Cut after a whole word, not inside one');
        $this->assertSame(' ', mb_substr($long, mb_strlen($kept), 1), 'Cut at a word boundary');
        $this->assertStringNotContainsString("\n", $excerpt);
    }

    #[Test]
    public function the_quote_is_set_at_the_largest_size_that_fits_the_measure(): void
    {
        $canvas = BrandCanvas::make(TributeCards::WIDTH, TributeCards::HEIGHT, 1);

        $short = $this->service->layout(new Tribute(['message' => 'A few words.']), $canvas);
        $this->assertSame(TributeCards::QUOTE_SIZES_PX[0], $short['size']);
        $this->assertCount(1, $short['lines']);
        $this->assertStringStartsWith(Typesetting::LEFT_DOUBLE_QUOTE, $short['lines'][0]);
        $this->assertStringEndsWith(Typesetting::RIGHT_DOUBLE_QUOTE, $short['lines'][0]);

        $long = $this->service->layout(new Tribute(['message' => str_repeat('A patient teacher who waited for the right question. ', 12)]), $canvas);
        $this->assertLessThan(TributeCards::QUOTE_SIZES_PX[0], $long['size']);
        $this->assertLessThanOrEqual(TributeCards::QUOTE_MAX_LINES, count($long['lines']));
        $this->assertStringEndsWith(Typesetting::RIGHT_DOUBLE_QUOTE, end($long['lines']));
    }
}
