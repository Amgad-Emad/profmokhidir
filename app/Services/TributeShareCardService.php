<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Brand;
use App\Constants\Disks;
use App\Constants\Locales;
use App\Constants\SocialCards;
use App\Constants\TributeCards;
use App\Constants\Typesetting;
use App\Models\Tribute;
use App\Repositories\Contracts\TributeRepositoryInterface;
use App\Support\BrandCanvas;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Traits\Localizable;

/**
 * One share card per approved tribute, in the brand's Instagram tribute layout
 * (App\Constants\TributeCards). A card is drawn once with the GD canvas, kept on
 * the public disk and its path remembered on the tribute; it is forgotten when
 * the wording changes or the tribute leaves the site, and drawn afresh on the
 * next request.
 *
 * Arabic pages share an Arabic edition of each card (TributeCards::path with the
 * locale): the labels in Arabic, kept beside the English card and forgotten with
 * it. Tribute text and names in Arabic are shaped and set in Amiri on either
 * edition (App\Support\BrandCanvas).
 */
class TributeShareCardService
{
    use Localizable;

    public function __construct(
        private readonly TributeRepositoryInterface $tributes,
        private readonly FilesystemFactory $filesystems,
        private readonly TypesetterService $typesetter,
    ) {}

    /**
     * Absolute path of the tribute's card, drawing it first when it is missing.
     * Null for tributes that are not approved. `locale` picks the edition (the
     * current language by default).
     */
    public function pathFor(Tribute $tribute, ?string $locale = null): ?string
    {
        if (! $tribute->isApproved()) {
            return null;
        }

        $locale ??= app()->getLocale();

        if ($locale !== Locales::DEFAULT && in_array($locale, Locales::all(), true)) {
            $relative = TributeCards::path((int) $tribute->getKey(), $locale);

            if (! $this->disk()->exists($relative)) {
                $this->withLocale($locale, fn () => $this->render($tribute)->png($this->disk()->path($relative)));
            }

            return $this->disk()->path($relative);
        }

        $relative = (string) $tribute->share_card_path;

        if ($relative === '' || ! $this->disk()->exists($relative)) {
            $relative = $this->generate($tribute);
        }

        return $this->disk()->path($relative);
    }

    /**
     * Draw the card and remember where it is. Returns the path on the public disk.
     */
    public function generate(Tribute $tribute): string
    {
        $relative = TributeCards::path((int) $tribute->getKey());

        $this->render($tribute)->png($this->disk()->path($relative));
        $this->tributes->update($tribute, ['share_card_path' => $relative]);

        return $relative;
    }

    /**
     * Draw the cards of every approved tribute that has none; returns how many were drawn.
     */
    public function generateMissing(): int
    {
        $count = 0;

        foreach ($this->tributes->approvedWithoutShareCard() as $tribute) {
            $this->generate($tribute);
            $count++;
        }

        return $count;
    }

    /**
     * Remove a cached card (after an edit, a rejection or a deletion).
     */
    public function forget(Tribute $tribute): void
    {
        foreach (Locales::all() as $locale) {
            $edition = TributeCards::path((int) $tribute->getKey(), $locale);

            if ($locale !== Locales::DEFAULT && $tribute->getKey() !== null && $this->disk()->exists($edition)) {
                $this->disk()->delete($edition);
            }
        }

        $relative = (string) $tribute->share_card_path;

        if ($relative !== '' && $this->disk()->exists($relative)) {
            $this->disk()->delete($relative);
        }

        if ($tribute->exists && $relative !== '') {
            $this->tributes->update($tribute, ['share_card_path' => null]);
        }
    }

    /**
     * Download name of a tribute's card.
     */
    public function filename(Tribute $tribute): string
    {
        return TributeCards::filename((int) $tribute->getKey());
    }

    /**
     * The tribute text as it is set on the card: typeset, trimmed to a word
     * boundary and closed with an ellipsis when it runs long.
     */
    public function excerpt(string $message): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $this->typesetter->text($message)) ?? '');

        if (mb_strlen($text) <= TributeCards::EXCERPT_MAX_CHARS) {
            return $text;
        }

        $cut = mb_substr($text, 0, TributeCards::EXCERPT_MAX_CHARS);
        $space = mb_strrpos($cut, ' ');

        if ($space !== false && $space > TributeCards::EXCERPT_MAX_CHARS / 2) {
            $cut = mb_substr($cut, 0, $space);
        }

        return $this->trimEnd($cut, "\u{2013}-").Typesetting::ELLIPSIS;
    }

    /**
     * The lines of the quote and the size they are set at: the largest size at
     * which the excerpt fits the card's measure in QUOTE_MAX_LINES lines.
     *
     * @return array{size: int, lines: list<string>}
     */
    public function layout(Tribute $tribute, BrandCanvas $canvas): array
    {
        $excerpt = $this->excerpt($tribute->message);
        // Arabic is quoted with guillemets, written in logical order so that, laid out
        // right to left, the text reads «...» as Arabic print sets it (ar-php draws them as given).
        [$open, $close] = BrandCanvas::isArabic($excerpt) ? ["\u{00AB}", "\u{00BB}"] : [Typesetting::LEFT_DOUBLE_QUOTE, Typesetting::RIGHT_DOUBLE_QUOTE];
        $quote = $open.$excerpt.$close;
        $measure = TributeCards::WIDTH - 2 * TributeCards::MARGIN_PX;
        $sizes = BrandCanvas::isArabic($excerpt) ? TributeCards::QUOTE_SIZES_ARABIC_PX : TributeCards::QUOTE_SIZES_PX;
        $lines = [];

        foreach ($sizes as $size) {
            $lines = $canvas->wrap($quote, SocialCards::FONT_DISPLAY_ITALIC, $size, $measure);

            if (count($lines) <= TributeCards::QUOTE_MAX_LINES) {
                return ['size' => $size, 'lines' => $lines];
            }
        }

        $size = end($sizes) ?: TributeCards::QUOTE_SIZES_PX[0];
        $lines = array_slice($lines, 0, TributeCards::QUOTE_MAX_LINES);
        $last = array_key_last($lines);
        $lines[$last] = $this->trimEnd($lines[$last], $close).Typesetting::ELLIPSIS.$close;

        return ['size' => $size, 'lines' => $lines];
    }

    /**
     * Strip trailing spaces and punctuation (Latin and Arabic, plus `extra`) before an ellipsis; multibyte-safe.
     */
    private function trimEnd(string $text, string $extra = ''): string
    {
        return (string) preg_replace('/[\s,;:.\x{060C}\x{061B}'.preg_quote($extra, '/').']+$/u', '', $text);
    }

    private function render(Tribute $tribute): BrandCanvas
    {
        $canvas = BrandCanvas::make(TributeCards::WIDTH, TributeCards::HEIGHT);
        $centre = (int) (TributeCards::WIDTH / 2);
        $margin = TributeCards::MARGIN_PX;

        // Head: "Remembering" in label style with a short brass rule beneath it.
        $canvas->tracked(mb_strtoupper(__(TributeCards::LABEL)), SocialCards::FONT_LABEL, TributeCards::LABEL_SIZE_PX, Brand::BRASS, $margin, 0.3);
        $canvas->rule(TributeCards::RULE_WIDTH_PX, $margin + 44);

        // The tribute, set in the display italic and centred vertically in the body of the card.
        ['size' => $size, 'lines' => $lines] = $this->layout($tribute, $canvas);
        $lineHeight = (int) round($size * (BrandCanvas::isArabic($tribute->message) ? TributeCards::QUOTE_LEADING_ARABIC : TributeCards::QUOTE_LEADING));
        $blockHeight = count($lines) * $lineHeight;
        $footTop = TributeCards::HEIGHT - $margin - TributeCards::MARK_SIZE_PX - 120;
        $bodyTop = $margin + 120;
        $quoteTop = (int) round($bodyTop + max(0, ($footTop - $bodyTop - $blockHeight - 110) / 2));

        foreach ($lines as $index => $line) {
            $canvas->centred($line, SocialCards::FONT_DISPLAY_ITALIC, $size, Brand::IVORY, $quoteTop + $index * $lineHeight);
        }

        // The name in spaced capitals (small caps), then relationship and place as a label.
        $nameTop = $quoteTop + $blockHeight + 40;
        $canvas->tracked(mb_strtoupper($this->typesetter->text($tribute->name)), SocialCards::FONT_DISPLAY, TributeCards::NAME_SIZE_PX, Brand::CHAMPAGNE, $nameTop, TributeCards::NAME_TRACKING_EM);

        $meta = array_values(array_filter([(string) $tribute->relationship?->label(), (string) $tribute->location]));

        if ($meta !== []) {
            $metaTop = $nameTop + (BrandCanvas::isArabic($tribute->name) ? TributeCards::META_GAP_ARABIC_PX : TributeCards::META_GAP_PX);
            $canvas->labelRuns($meta, '  ·  ', 13, Brand::BRASS, $metaTop, 0.3, Locales::dir(app()->getLocale()) === Locales::RTL);
        }

        // Foot: the mark in its ring above the domain.
        $markCentreY = TributeCards::HEIGHT - $margin - (int) (TributeCards::MARK_SIZE_PX / 2) - 40;
        $canvas->mark($centre, $markCentreY, TributeCards::MARK_SIZE_PX);
        $canvas->tracked(mb_strtoupper(TributeCards::DOMAIN), SocialCards::FONT_LABEL, 13, Brand::BRASS, TributeCards::HEIGHT - $margin + 4, 0.3);

        return $canvas;
    }

    private function disk(): Filesystem
    {
        return $this->filesystems->disk(Disks::PUBLIC);
    }
}
