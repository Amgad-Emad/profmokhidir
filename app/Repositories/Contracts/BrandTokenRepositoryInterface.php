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
     * The functional exception to the palette (the danger red), token name => hex.
     *
     * @return array<string, string>
     */
    public function functionalColours(): array;

    /**
     * @return array<string, array{role: string, usage: string}>
     */
    public function functionalColourRoles(): array;

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

    /**
     * Rules the typesetter applies to database text.
     *
     * @return array{units: list<string>, bindingAbbreviations: list<string>, capsExceptions: list<string>}
     */
    public function typesettingRules(): array;

    /**
     * OpenType features per context, and the rhythm and measure tokens.
     *
     * @return array{features: array<string, array{context: string, css: string, features: list<string>}>, rhythm: array<string, int>, baseline: int, bodyLine: int, measure: array{min: int, max: int}}
     */
    public function typesetting(): array;

    /**
     * @return array<string, array{label: string, width: int, height: int, shapes: list<array<string, string|float|int>>}>
     */
    public function ornaments(): array;

    /**
     * The Blade components, grouped for the style guide (App\Constants\Components).
     *
     * @return array<string, list<array{tag: string, view: string, group: string, purpose: string, usage: string}>>
     */
    public function components(): array;

    /**
     * Group key => heading, in display order.
     *
     * @return array<string, string>
     */
    public function componentGroups(): array;
}
