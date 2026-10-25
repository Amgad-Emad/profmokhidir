<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Sections;
use App\Models\Profile;
use App\Support\RichText;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Assembles the About page from the published profile: sanitised and typeset
 * biography, expertise tags, key facts, optional public contact details and the
 * links on to the CV, Publications and Awards sections.
 */
class AboutPageService
{
    public function __construct(
        private readonly ProfileService $profiles,
        private readonly NavigationService $navigation,
        private readonly SeoService $seo,
        private readonly SettingService $settings,
        private readonly TypesetterService $typesetter,
        private readonly SectionCardService $cards,
    ) {}

    /**
     * @return array{
     *     section: array{key: string, label: string, title: string, url: string, route: string, placement: string, summary: string, active: bool},
     *     chapter: int,
     *     profile: Profile|null,
     *     portrait: Media|null,
     *     bio: string,
     *     expertise: list<string>,
     *     facts: list<array{label: string, value: string}>,
     *     contact: array{email: string|null, note: string|null, phone: string|null, address: string|null},
     *     links: list<array{key: string, numeral: string, title: string, summary: string, url: string, image: Media|null}>,
     *     description: string|null,
     *     schema: array<string, mixed>|null
     * }
     */
    public function data(): array
    {
        $profile = $this->profiles->get();
        $section = $this->navigation->section(Sections::ABOUT, Sections::ABOUT);
        $portrait = $profile?->portrait();

        return [
            'section' => $section,
            'chapter' => $this->navigation->chapter(Sections::ABOUT),
            'profile' => $profile,
            'portrait' => $portrait,
            'bio' => $this->typesetter->html(RichText::sanitise($profile?->full_bio)),
            'expertise' => $this->expertise($profile),
            'facts' => $this->facts($profile),
            'contact' => $this->contact($profile),
            'links' => $this->links(),
            'description' => $this->seo->description($profile?->short_bio, $section['summary']),
            'schema' => $profile === null ? null : $this->seo->person($profile, $portrait, $section['url']),
        ];
    }

    /**
     * The section cards on to the CV, Publications and Awards.
     *
     * @return list<array{key: string, numeral: string, title: string, summary: string, url: string, image: Media|null}>
     */
    public function links(): array
    {
        return $this->cards->cards(Sections::aboutLinks());
    }

    /**
     * The profile's own contact email and note; without them, the site
     * contact details from the settings.
     *
     * @return array{email: string|null, note: string|null, phone: string|null, address: string|null}
     */
    public function contact(?Profile $profile): array
    {
        $site = $this->settings->contact();

        return [
            'email' => filled($profile?->contact_email_public) ? $profile->contact_email_public : $site['email'],
            'note' => filled($profile?->contact_note) ? $profile->contact_note : null,
            'phone' => $site['phone'],
            'address' => $site['address'],
        ];
    }

    /**
     * @return list<string>
     */
    private function expertise(?Profile $profile): array
    {
        $items = is_array($profile?->expertise) ? $profile->expertise : [];

        return array_values(array_filter(
            array_map(fn ($item): string => is_string($item) ? $this->typesetter->text(trim($item)) : '', $items),
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
                $facts[] = ['label' => $label, 'value' => $this->typesetter->text($value)];
            }
        }

        return $facts;
    }
}
