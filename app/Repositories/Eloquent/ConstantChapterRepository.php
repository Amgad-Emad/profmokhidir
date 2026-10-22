<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Constants\Chapters;
use App\Repositories\Contracts\ChapterRepositoryInterface;

/**
 * Chapter definitions served from app/Constants/Chapters.php.
 */
final class ConstantChapterRepository implements ChapterRepositoryInterface
{
    public function all(): array
    {
        return Chapters::definitions();
    }

    public function find(string $key): ?array
    {
        return Chapters::definitions()[$key] ?? null;
    }

    public function chapterOf(string $section): ?string
    {
        foreach (Chapters::definitions() as $key => $chapter) {
            if (in_array($section, $chapter['sections'], true)) {
                return $key;
            }
        }

        return null;
    }

    public function description(string $section): ?string
    {
        return Chapters::descriptions()[$section] ?? null;
    }

    public function action(string $key): ?array
    {
        return Chapters::actions()[$key] ?? null;
    }
}
