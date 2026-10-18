<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Constants\Sections;
use App\Repositories\Contracts\SectionRepositoryInterface;

/**
 * Section definitions served from app/Constants/Sections.php.
 */
final class ConstantSectionRepository implements SectionRepositoryInterface
{
    public function all(): array
    {
        return Sections::definitions();
    }

    public function find(string $key): ?array
    {
        return Sections::definitions()[$key] ?? null;
    }

    public function withPlacement(string $placement): array
    {
        return array_filter(
            Sections::definitions(),
            fn (array $section): bool => $section['placement'] === $placement,
        );
    }
}
