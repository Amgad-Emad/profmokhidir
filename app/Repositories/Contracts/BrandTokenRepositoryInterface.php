<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

/**
 * Read access to the brand design tokens. Tokens are fixed values defined in
 * app/Constants (and compiled into CSS), so there is no database table behind
 * this interface; it exists so services never read constants directly and the
 * source could move later without touching them.
 */
interface BrandTokenRepositoryInterface
{
    /**
     * @return array<string, string> token name => hex
     */
    public function colours(): array;

    /**
     * @return array<string, array{role: string, usage: string}>
     */
    public function colourRoles(): array;

    /**
     * @return array<string, array{family: string, weights: list<int>, italic: bool, use: string}>
     */
    public function fonts(): array;

    /**
     * @return array<string, array<string, mixed>>
     */
    public function typeScale(): array;

    /**
     * @return array<string, array{attribute: string, summary: string}>
     */
    public function motion(): array;
}
