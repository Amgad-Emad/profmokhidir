<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Typesetting;
use App\Repositories\Contracts\BrandTokenRepositoryInterface;
use Illuminate\Support\HtmlString;

/**
 * Typesets text from the database as it is rendered: curly quotes and
 * apostrophes, en dashes for year and page ranges, an ellipsis character, and
 * non-breaking spaces before units, between initials and after abbreviated
 * titles. In HTML it also sets runs of capitals in small caps and hangs the
 * opening quotation mark of a pull quote. Tags, attributes, URLs, email
 * addresses and code are never changed.
 */
class TypesetterService
{
    /** Elements whose text is left exactly as written. */
    private const array VERBATIM_TAGS = ['code', 'pre', 'kbd', 'samp', 'script', 'style'];

    /** Elements that start a new run of text, so quote direction starts afresh. */
    private const array BLOCK_TAGS = ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'li', 'ul', 'ol', 'br', 'div', 'figcaption', 'dd', 'dt', 'td', 'th'];

    private const string PROTECTED_PATTERN = '~(https?://\S+|www\.\S+|[^\s@<>]+@[^\s@<>]+\.[^\s@<>]+)~u';

    private const string YEAR = '(?:1[5-9]|20)\d{2}';

    /** @var array{units: list<string>, bindingAbbreviations: list<string>, capsExceptions: list<string>}|null */
    private ?array $rules = null;

    public function __construct(private readonly BrandTokenRepositoryInterface $tokens) {}

    /**
     * Typeset plain text. The result is plain Unicode text: escape it when echoing.
     */
    public function text(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        $previous = '';

        return $this->typesetRun($text, $previous);
    }

    /**
     * Typeset plain text and return it escaped, with runs of capitals in small caps.
     * Safe to echo unescaped (the @typeset Blade directive does exactly that).
     */
    public function inline(?string $text): HtmlString
    {
        return new HtmlString($this->wrapCaps(e($this->text($text))));
    }

    /**
     * Typeset the text nodes of (already sanitised) HTML.
     */
    public function html(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        $parts = preg_split('/(<[^>]*>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];
        $previous = '';
        $verbatim = 0;
        $quoteStart = false;
        $output = '';

        foreach ($parts as $part) {
            if ($part[0] === '<') {
                $output .= $part;

                if (! preg_match('~^<\s*(/?)\s*([a-z0-9]+)~i', $part, $tag)) {
                    continue;
                }

                $name = strtolower($tag[2]);
                $closing = $tag[1] === '/';

                if (in_array($name, self::VERBATIM_TAGS, true)) {
                    $verbatim = max(0, $verbatim + ($closing ? -1 : 1));
                }

                if (in_array($name, self::BLOCK_TAGS, true)) {
                    $previous = '';
                }

                if ($name === 'blockquote') {
                    $quoteStart = ! $closing;
                }

                continue;
            }

            if ($verbatim > 0) {
                $output .= $part;

                continue;
            }

            $decoded = html_entity_decode($part, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $typeset = $this->wrapCaps(htmlspecialchars($this->typesetRun($decoded, $previous), ENT_NOQUOTES | ENT_HTML5, 'UTF-8'));

            if ($quoteStart && trim($decoded) !== '') {
                $typeset = $this->hangOpeningQuote($typeset);
                $quoteStart = false;
            }

            $output .= $typeset;
        }

        return $output;
    }

    /**
     * Capital Roman numeral for a chapter number (1 => "I"); numbers outside 1-3999 stay Arabic.
     */
    public function roman(int $number): string
    {
        if ($number < 1 || $number > 3999) {
            return (string) $number;
        }

        $roman = '';

        foreach (Typesetting::romanSymbols() as $value => $symbol) {
            while ($number >= $value) {
                $roman .= $symbol;
                $number -= $value;
            }
        }

        return $roman;
    }

    /**
     * Typeset one run of decoded text. $previous carries the character before the
     * run (across inline tags) so quote direction is decided correctly.
     */
    private function typesetRun(string $text, string &$previous): string
    {
        $chunks = preg_split(self::PROTECTED_PATTERN, $text, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$text];
        $result = '';

        foreach ($chunks as $index => $chunk) {
            if ($chunk === '') {
                continue;
            }

            // Odd indexes are the captured URLs and email addresses: copied as they are.
            $result .= $index % 2 === 1 ? $chunk : $this->typesetChunk($chunk, $previous);
            $previous = mb_substr($chunk, -1);
        }

        return $result;
    }

    private function typesetChunk(string $text, string $previous): string
    {
        $text = $this->quotes($text, $previous);
        $text = str_replace('...', Typesetting::ELLIPSIS, $text);

        $year = self::YEAR;
        $text = (string) preg_replace("/(?<![\\d\\-–])({$year})\\s*[-–]\\s*({$year})(?![\\d\\-–])/u", '$1'.Typesetting::EN_DASH.'$2', $text);
        $text = (string) preg_replace("/(?<![\\d\\-–])({$year})\\s*[-–]\\s*(present|today)\\b/ui", '$1'.Typesetting::EN_DASH.'$2', $text);
        $text = (string) preg_replace('/\b(pp?\.\s?\d+)\s*-\s*(\d+)\b/u', '$1'.Typesetting::EN_DASH.'$2', $text);
        // A spaced hyphen (or double hyphen) between words is a spaced en dash.
        $text = (string) preg_replace('/(?<=\S) +-{1,2} +(?=\S)/u', ' '.Typesetting::EN_DASH.' ', $text);
        $text = str_replace('--', Typesetting::EN_DASH, $text);

        return $this->spaces($text);
    }

    /**
     * Straight quotes to curly quotes and apostrophes, decided by the neighbouring characters.
     */
    private function quotes(string $text, string $previous): string
    {
        if (! str_contains($text, '"') && ! str_contains($text, "'")) {
            return $text;
        }

        $characters = mb_str_split($text);
        $count = count($characters);

        foreach ($characters as $i => $character) {
            if ($character !== '"' && $character !== "'") {
                $previous = $character;

                continue;
            }

            $next = $characters[$i + 1] ?? '';
            $opens = $previous === '' || preg_match('/[\s(\[{\x{2014}\x{2013}\/\x{201C}\x{2018}-]/u', $previous) === 1;

            if ($character === "'") {
                $abbreviatedYear = $opens && preg_match('/^\d{2}(?!\d)/', implode('', array_slice($characters, $i + 1, 3))) === 1;

                if ($abbreviatedYear || (! $opens && preg_match('/\p{L}|\d/u', $next) === 1)) {
                    $characters[$i] = Typesetting::RIGHT_SINGLE_QUOTE;
                } else {
                    $characters[$i] = $opens ? Typesetting::LEFT_SINGLE_QUOTE : Typesetting::RIGHT_SINGLE_QUOTE;
                }
            } else {
                $characters[$i] = $opens ? Typesetting::LEFT_DOUBLE_QUOTE : Typesetting::RIGHT_DOUBLE_QUOTE;
            }

            $previous = $characters[$i];
        }

        return $count > 0 ? implode('', $characters) : $text;
    }

    /**
     * Non-breaking spaces: before units, between initials and after abbreviated titles.
     */
    private function spaces(string $text): string
    {
        $rules = $this->rules();

        $units = implode('|', array_map(fn (string $unit): string => preg_quote($unit, '/'), $rules['units']));
        $text = (string) preg_replace("/(\\d) +({$units})(?![\\p{L}\\d])/u", '$1'.Typesetting::NARROW_NBSP.'$2', $text);

        // "M. O. Khidir": an initial keeps the next initial or surname on its line.
        $text = (string) preg_replace('/(?<![\p{L}.])(\p{Lu}\.) +(?=\p{Lu})/u', '$1'.Typesetting::NBSP, $text);

        $abbreviations = implode('|', array_map(fn (string $word): string => preg_quote($word, '/'), $rules['bindingAbbreviations']));

        return (string) preg_replace("/(?<![\\p{L}])({$abbreviations}) +(?=[\\p{L}\\d])/u", '$1'.Typesetting::NBSP, $text);
    }

    /**
     * Wrap runs of two or more capitals (FAO, UNESCO) so they set in small caps. Expects escaped text.
     */
    private function wrapCaps(string $escaped): string
    {
        $exceptions = $this->rules()['capsExceptions'];

        return (string) preg_replace_callback(
            '/(?<![\p{L}\d&#])(\p{Lu}{2,}[\p{Lu}\d]*)(?![\p{L}\d;])/u',
            fn (array $match): string => in_array($match[1], $exceptions, true)
                ? $match[1]
                : '<span class="'.Typesetting::CAPS_CLASS.'">'.$match[1].'</span>',
            $escaped,
        );
    }

    private function hangOpeningQuote(string $html): string
    {
        return (string) preg_replace(
            '/^(\s*)(['.Typesetting::LEFT_DOUBLE_QUOTE.Typesetting::LEFT_SINGLE_QUOTE.'])/u',
            '$1<span class="'.Typesetting::HANG_CLASS.'">$2</span>',
            $html,
            1,
        );
    }

    /**
     * @return array{units: list<string>, bindingAbbreviations: list<string>, capsExceptions: list<string>}
     */
    private function rules(): array
    {
        return $this->rules ??= $this->tokens->typesettingRules();
    }
}
