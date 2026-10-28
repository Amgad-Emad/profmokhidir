<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\ColourTheme;
use Illuminate\Database\Eloquent\Collection;

/**
 * Storage of the colour themes (Admin › Appearance).
 */
interface ColourThemeRepositoryInterface
{
    public function find(int $id): ?ColourTheme;

    public function findBySlug(string $slug): ?ColourTheme;

    /**
     * The theme the site is drawn with, or null when the brand defaults apply.
     */
    public function active(): ?ColourTheme;

    /**
     * Every theme: presets first, then custom themes by name.
     *
     * @return Collection<int, ColourTheme>
     */
    public function all(): Collection;

    public function slugExists(string $slug, ?int $ignoreId = null): bool;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): ColourTheme;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(ColourTheme $theme, array $attributes): ColourTheme;

    public function delete(ColourTheme $theme): bool;

    /**
     * Make one theme the active one (and no other).
     */
    public function activate(ColourTheme $theme): ColourTheme;
}
