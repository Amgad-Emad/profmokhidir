<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Sections;
use App\Models\Profile;
use App\Support\RichText;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Assembles the About page from the published profile: sanitised biography,
 * expertise tags, key facts, optional public contact details and the links on
 * to the CV, Publications and Awards sections.
 */
class AboutPageService
{
    public function __construct(
        private readonly ProfileService $profiles,
        private readonly NavigationService $navigation,
    ) {}

    /**
     * @return array{
     *     section: array{key: string, label: string, title: string, url: string, route: string, placement: string, summary: string, active: bool},
     *     profile: Profile|null,
     *     portrait: Media|null,
     *     bio: string,
     *     expertise: list<string>,
     *     facts: list<array{label: string, value: string}>,
     *     contact: array{email: string|null, note: string|null},
     *     links: list<array{key: string, label: string, title: string, url: string, route: string, placement: string, summary: string, active: bool}>
     * }
     */
    public function data(): array
    {
        $profile = $this->profiles->get();

        return [
            'section' => $this->navigation->section(Sections::ABOUT, Sections::ABOUT),
            'profile' => $profile,
            'portrait' => $profile?->portrait(),
            'bio' => RichText::sanitise($profile?->full_bio),
            'expertise' => $this->expertise($profile),
            'facts' => $this->facts($profile),
            'contact' => [
                'email' => filled($profile?->contact_email_public) ? $profile->contact_email_public : null,
                'note' => filled($profile?->contact_note) ? $profile->contact_note : null,
            ],
            'links' => $this->links(),
        ];
    }

    /**
     * @return list<array{key: string, label: string, title: string, url: string, route: string, placement: string, summary: string, active: bool}>
     */
    public function links(): array
    {
        return array_map(fn (string $key): array => $this->navigation->section($key), Sections::aboutLinks());
    }

    /**
     * @return list<string>
     */
    private function expertise(?Profile $profile): array
    {
        $items = is_array($profile?->expertise) ? $profile->expertise : [];

        return array_values(array_filter(
            array_map(fn ($item): string => is_string($item) ? trim($item) : '', $items),
            fn (string $item): bool => $item !== '',
        ));
    }

    /**
     * Key facts are stored as [{label, value}]; anything incomplete is dropped.
     *
     * @return list<array{label: string, value: string}>
     */
    private function facts(?Profile $profile): array
    {
        $items = is_array($profile?->key_facts) ? $profile->key_facts : [];
        $facts = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $label = trim((string) ($item['label'] ?? ''));
            $value = trim((string) ($item['value'] ?? ''));

            if ($label !== '' && $value !== '') {
                $facts[] = ['label' => $label, 'value' => $value];
            }
        }

        return $facts;
    }
}
