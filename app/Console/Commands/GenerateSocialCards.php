<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Constants\Brand;
use App\Constants\Locales;
use App\Constants\SocialCards;
use App\Services\TributeShareCardService;
use App\Support\BrandCanvas;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Renders the static Open Graph share cards (1200×630 PNG) once, into public/:
 * evergreen with the soft brass glow, the MK mark in its hairline ring, the
 * brand quote in Cormorant Garamond Light Italic and the domain in label style.
 * With --tributes it also draws (once) the share card of every approved
 * tribute that has none yet. Nothing here runs at request time. Every card is
 * drawn once per language: the Arabic edition carries the quote in Arabic,
 * shaped and set in Amiri (App\Support\BrandCanvas).
 */
#[Signature('brand:social-cards {--output= : Directory to write into (defaults to public/)} {--tributes : Also draw the missing share cards of approved tributes}')]
#[Description('Generate the static Open Graph share images into public/')]
class GenerateSocialCards extends Command
{
    private const int QUOTE_SIZE_PX = 60;

    /** Amiri sets larger and deeper than Cormorant at the same size: a smaller size and more leading. */
    private const int QUOTE_SIZE_ARABIC_PX = 46;

    private const float QUOTE_LEADING = 1.25;

    private const float QUOTE_LEADING_ARABIC = 1.8;

    private const int QUOTE_MAX_WIDTH_PX = 980;

    private const int LABEL_SIZE_PX = 15;

    private const float LABEL_TRACKING_EM = 0.3;

    private const int RULE_WIDTH_PX = 48;

    public function handle(TributeShareCardService $tributeCards): int
    {
        if (! BrandCanvas::supported()) {
            $this->error('The GD extension with FreeType support is required.');

            return self::FAILURE;
        }

        $directory = rtrim((string) ($this->option('output') ?: public_path()), '/');

        foreach (Locales::all() as $locale) {
            foreach (SocialCards::allFor($locale) as $section => $relativePath) {
                $path = $directory.'/'.$relativePath;

                $this->render(__(SocialCards::QUOTE, [], $locale))->png($path);

                $name = $locale === Locales::DEFAULT ? $section : $section.' ('.$locale.')';

                $this->components->info(sprintf('%s card written to %s (%d×%d).', $name, $path, SocialCards::WIDTH, SocialCards::HEIGHT));
            }
        }

        if ($this->option('tributes')) {
            $count = $tributeCards->generateMissing();

            $this->components->info(sprintf('%d tribute share %s drawn.', $count, $count === 1 ? 'card' : 'cards'));
        }

        return self::SUCCESS;
    }

    private function render(string $quote): BrandCanvas
    {
        $canvas = BrandCanvas::make(SocialCards::WIDTH, SocialCards::HEIGHT);

        // Mark: the logo artwork (book, sprout and rays), centred near the top.
        $canvas->mark((int) (SocialCards::WIDTH / 2), 166, SocialCards::MARK_SIZE_PX);

        // Quote in the display italic, wrapped to the card's measure and centred.
        $arabic = BrandCanvas::isArabic($quote);
        $size = $arabic ? self::QUOTE_SIZE_ARABIC_PX : self::QUOTE_SIZE_PX;
        $lines = $canvas->wrap($quote, SocialCards::FONT_DISPLAY_ITALIC, $size, self::QUOTE_MAX_WIDTH_PX);
        $lineHeight = (int) round($size * ($arabic ? self::QUOTE_LEADING_ARABIC : self::QUOTE_LEADING));
        $quoteTop = $arabic ? 300 : 316;

        foreach ($lines as $index => $line) {
            $canvas->centred($line, SocialCards::FONT_DISPLAY_ITALIC, $size, Brand::IVORY, $quoteTop + $index * $lineHeight);
        }

        // Short brass rule, then the domain in label style (uppercase, 0.3em tracking).
        $ruleY = $quoteTop + count($lines) * $lineHeight + 48;
        $canvas->rule(self::RULE_WIDTH_PX, $ruleY);
        $canvas->tracked(mb_strtoupper(SocialCards::DOMAIN), SocialCards::FONT_LABEL, self::LABEL_SIZE_PX, Brand::BRASS, $ruleY + 56, self::LABEL_TRACKING_EM);

        return $canvas;
    }
}
