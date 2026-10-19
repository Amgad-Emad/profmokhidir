<?php

declare(strict_types=1);

namespace App\Services\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Unique slug generation for content with detail pages. The using service's
 * repository must expose slugExists(string, ?int).
 */
trait GeneratesSlugs
{
    /**
     * Fill `slug` from `title` (or an explicit slug) and make it unique.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function withUniqueSlug(array $data, ?Model $existing = null, string $source = 'title'): array
    {
        $candidate = trim((string) ($data['slug'] ?? ''));

        if ($candidate === '' && $existing !== null && ! array_key_exists($source, $data)) {
            return $data;
        }

        if ($candidate === '') {
            $candidate = (string) ($data[$source] ?? $existing?->getAttribute($source) ?? '');
        }

        if ($existing !== null && array_key_exists('slug', $data) === false && $existing->getAttribute('slug')) {
            // Title edits do not change an existing slug (links stay stable).
            return $data;
        }

        $data['slug'] = $this->uniqueSlug($candidate, $existing?->getKey());

        return $data;
    }

    protected function uniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source) ?: Str::lower(Str::random(8));
        $slug = $base;
        $suffix = 2;

        while ($this->repository->slugExists($slug, $ignoreId)) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
