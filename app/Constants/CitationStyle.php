<?php

declare(strict_types=1);

namespace App\Constants;

use App\Constants\Concerns\HasValues;

/**
 * The citation formats offered by "Cite" on the publications catalogue, in the
 * order the panel lists them. App\Services\CitationFormatterService writes them.
 */
enum CitationStyle: string
{
    use HasValues;

    case Apa = 'apa';
    case Mla = 'mla';
    case Chicago = 'chicago';
    case Bibtex = 'bibtex';

    public function label(): string
    {
        return match ($this) {
            self::Apa => 'APA',
            self::Mla => 'MLA',
            self::Chicago => 'Chicago',
            self::Bibtex => 'BibTeX',
        };
    }

    /**
     * The edition of the style followed, shown as a quiet note in the panel.
     */
    public function edition(): string
    {
        return match ($this) {
            self::Apa => '7th edition',
            self::Mla => '9th edition',
            self::Chicago => '17th edition, bibliography',
            self::Bibtex => 'For LaTeX reference managers',
        };
    }

    /**
     * BibTeX is code, set in the mono face; the others are prose.
     */
    public function isCode(): bool
    {
        return $this === self::Bibtex;
    }
}
