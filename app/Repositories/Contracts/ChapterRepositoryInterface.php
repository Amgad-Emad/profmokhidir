<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

/**
 * Read access to the masthead's chapter definitions. Chapters are a fixed
 * product decision held in app/Constants/Chapters.php, so no table backs this
 * interface; it exists so services never read constants directly.
 *
 * @phpstan-type ChapterDefinition array{label: string, strap: string, sections: list<string>, actions: list<string>, featured: string|null}
 */
interface ChapterRepositoryInterface
{
    /**
     * All chapters in masthead order.
     *
     * @return array<string, ChapterDefinition>
     */
    public function all(): array;

    /**
     * @return ChapterDefinition|null
     */
    public function find(string $key): ?array;

    /**
     * The chapter a section belongs to, or null when it is in none.
     */
    public function chapterOf(string $section): ?string;

    /**
     * One-line description of a section, for the panels.
     */
    public function description(string $section): ?string;

    /**
     * @return array{label: string, description: string, route: string, audience: string}|null
     */
    public function action(string $key): ?array;
}
