<?php

namespace Tests\Feature\Console;

use App\Constants\Sections;
use App\Constants\SocialCards;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GenerateSocialCardsTest extends TestCase
{
    #[Test]
    public function it_writes_each_card_as_a_1200_by_630_png_into_the_given_directory(): void
    {
        $directory = storage_path('framework/testing/social-cards-'.uniqid());

        try {
            $this->artisan('brand:social-cards', ['--output' => $directory])
                ->expectsOutputToContain('tributes card written to')
                ->assertSuccessful();

            $this->assertSame([Sections::TRIBUTES => 'images/social/tributes.png'], SocialCards::all());
            $this->assertSame(SocialCards::TRIBUTES, SocialCards::for(Sections::TRIBUTES));
            $this->assertNull(SocialCards::for(Sections::ABOUT));

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
}
