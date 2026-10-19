<?php

declare(strict_types=1);

namespace App\Constants;

use App\Constants\Concerns\HasValues;

enum PublicationType: string
{
    use HasValues;

    case Book = 'book';
    case JournalArticle = 'journal_article';
    case Chapter = 'chapter';
    case ConferencePaper = 'conference_paper';
    case Report = 'report';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Book => 'Book',
            self::JournalArticle => 'Journal article',
            self::Chapter => 'Book chapter',
            self::ConferencePaper => 'Conference paper',
            self::Report => 'Report',
            self::Other => 'Other',
        };
    }
}
