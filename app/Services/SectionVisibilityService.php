<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Locales;
use App\Constants\Permissions;
use App\Constants\SettingKeys;
use App\Constants\Sections;
use App\Models\User;

/**
 * Which public sections visitors may see. An administrator can hide a section
 * in Site settings: it leaves the navigation, the home and About cards, search
 * and the sitemap, and its pages answer 404 to visitors. Staff who manage
 * content can still open it, to check it before it is shown again.
 */
class SectionVisibilityService
{
    public function __construct(private readonly SettingService $settings) {}

    /**
     * Keys of the hidden sections, in section order.
     *
     * @return list<string>
     */
    public function hidden(): array
    {
        $stored = array_map('trim', explode(',', (string) $this->settings->get(SettingKeys::HIDDEN_SECTIONS)));

        return array_values(array_intersect(Sections::all(), $stored));
    }

    public function isVisible(string $key): bool
    {
        return ! in_array($key, $this->hidden(), true);
    }

    /**
     * The given section keys without the hidden ones, order kept.
     *
     * @param  list<string>  $keys
     * @return list<string>
     */
    public function visible(array $keys): array
    {
        $hidden = $this->hidden();

        return array_values(array_filter($keys, fn (string $key): bool => ! in_array($key, $hidden, true)));
    }

    /**
     * Whether this person may open a section's pages.
     */
    public function canView(string $key, ?User $user): bool
    {
        return $this->isVisible($key) || (bool) $user?->can(Permissions::MANAGE_CONTENT);
    }

    /**
     * The section a public route belongs to (its own route or a nested one,
     * in any language), or null for pages outside the sections.
     */
    public function sectionForRoute(?string $routeName): ?string
    {
        $name = (string) Locales::baseRouteName((string) $routeName);

        foreach (Sections::definitions() as $key => $definition) {
            if ($name === $definition['route'] || str_starts_with($name, $definition['route'].'.')) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Store which sections are hidden (unknown keys are ignored).
     *
     * @param  list<string>  $keys
     */
    public function hide(array $keys, ?User $actor = null): void
    {
        $keys = array_values(array_intersect(Sections::all(), $keys));

        $this->settings->update([SettingKeys::HIDDEN_SECTIONS => $keys === [] ? null : implode(',', $keys)], $actor);
    }
}
