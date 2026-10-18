<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

/**
 * Read access to the site's section definitions. Sections are a fixed product
 * decision held in app/Constants/Sections.php, so no table backs this interface;
 * it exists so services never read constants directly.
 */
interface SectionRepositoryInterface
{
    /**
     * All sections in navigation order.
     *
     * @return array<string, array{label: string, title: string, path: string, route: string, placement: string, summary: string}>
     */
    public function all(): array;

    /**
     * @return array{label: string, title: string, path: string, route: string, placement: string, summary: string}|null
     */
    public function find(string $key): ?array;

    /**
     * Sections with the given placement, in navigation order.
     *
     * @return array<string, array{label: string, title: string, path: string, route: string, placement: string, summary: string}>
     */
    public function withPlacement(string $placement): array;
}
