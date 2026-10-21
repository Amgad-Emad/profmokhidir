<?php

namespace Tests\Feature\Console;

use App\Constants\Disks;
use App\Constants\Sections;
use App\Constants\SocialCards;
use App\Constants\TributeCards;
use App\Models\Tribute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GenerateSocialCardsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_each_card_as_a_1200_by_630_png_into_the_given_directory(): void
    {
        $directory = storage_path('framework/testing/social-cards-'.uniqid());

        try {
            $this->artisan('brand:social-cards', ['--output' => $directory])
                ->expectsOutputToContain('site card written to')
                ->expectsOutputToContain('tributes card written to')
                ->assertSuccessful();

            $this->assertSame([SocialCards::KEY_SITE => 'images/social/site.png', Sections::TRIBUTES => 'images/social/tributes.png'], SocialCards::all());
            $this->assertSame(SocialCards::TRIBUTES, SocialCards::for(Sections::TRIBUTES));
            $this->assertNull(SocialCards::for(Sections::ABOUT));
            $this->assertSame(SocialCards::SITE, SocialCards::forOrDefault(Sections::ABOUT));
            $this->assertSame(SocialCards::SITE, SocialCards::forOrDefault(null));
            $this->assertSame(SocialCards::TRIBUTES, SocialCards::forOrDefault(Sections::TRIBUTES));
            $this->assertFileExists($directory.'/'.SocialCards::SITE);

            $path = $directory.'/'.SocialCards::TRIBUTES;
            $this->assertFileExists($path);

            $size = getimagesize($path);
            $this->assertSame([1200, 630, 'image/png'], [$size[0], $size[1], $size['mime']]);
            $this->assertSame([1200, 630], [SocialCards::WIDTH, SocialCards::HEIGHT]);
        } finally {
            File::deleteDirectory($directory);
        }
    }

    #[Test]
    public function the_fonts_it_draws_with_are_present(): void
    {
        foreach ([SocialCards::FONT_DISPLAY, SocialCards::FONT_DISPLAY_ITALIC, SocialCards::FONT_LABEL] as $font) {
            $this->assertFileExists(resource_path('fonts/'.$font));
        }

        $this->assertFileExists(resource_path('fonts/OFL.txt'));
    }

    #[Test]
    public function with_tributes_it_draws_the_missing_tribute_share_cards_once(): void
    {
        Storage::fake(Disks::PUBLIC);
        $directory = storage_path('framework/testing/social-cards-'.uniqid());
        $approved = Tribute::factory()->approved()->count(2)->create();
        Tribute::factory()->pending()->create();

        try {
            $this->artisan('brand:social-cards', ['--output' => $directory, '--tributes' => true])
                ->expectsOutputToContain('2 tribute share cards drawn.')
                ->assertSuccessful();

            foreach ($approved as $tribute) {
                Storage::disk(Disks::PUBLIC)->assertExists(TributeCards::path($tribute->id));
                $this->assertSame(TributeCards::path($tribute->id), $tribute->fresh()->share_card_path);
            }

            $this->assertCount(2, Storage::disk(Disks::PUBLIC)->allFiles());

            $this->artisan('brand:social-cards', ['--output' => $directory, '--tributes' => true])
                ->expectsOutputToContain('0 tribute share cards drawn.')
                ->assertSuccessful();
        } finally {
            File::deleteDirectory($directory);
        }
    }
}
