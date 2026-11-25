<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Pages;
use App\Constants\Sections;
use App\Models\Profile;
use App\Support\RichText;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Assembles the About page from the published profile: sanitised and typeset
 * biography, expertise tags, key facts, optional public contact details and the
 * links on to the CV, Publications and Awards sections. The page's own words
 * (Admin › Pages › About) show only where an editor entered them: no built-in
 * copy, placeholders or empty-state line.
 */
class AboutPageService
{
    public function __construct(
        private readonly ProfileService $profiles,
        private readonly NavigationService $navigation,
        private readonly SeoService $seo,
        private readonly TypesetterService $typesetter,
        private readonly SectionCardService $cards,
        private readonly PageContentService $pages,
    ) {}

    /**
     * @return array{
     *     section: array{key: string, label: string, title: string, url: string, route: string, placement: string, summary: string, active: bool},
     *     chapter: int,
     *     profile: Profile|null,
     *     portrait: Media|null,
     *     person: array{name: string, title: string, affiliation: string},
     *     bio: string,
     *     expertise: list<string>,
     *     facts: list<array{label: string, value: string}>,
     *     contact: array{email: string|null, note: string|null, phone: string|null, address: string|null},
     *     links: list<array{key: string, numeral: string, title: string, summary: string, url: string, image: Media|null}>,
     *     description: string|null,
     *     schema: array<string, mixed>|null,
     *     copy: array{title: string, epigraph: string, expertise: string, facts: string, contact: string}
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
            'person' => [
                'name' => $this->own($profile, 'name'),
                'title' => $this->own($profile, 'title'),
                'affiliation' => $this->own($profile, 'affiliation'),
            ],
            'bio' => $this->typesetter->html(RichText::sanitise($this->own($profile, 'full_bio'))),
            'expertise' => $this->expertise($profile),
            'facts' => $this->facts($profile),
            'contact' => $this->contact($profile),
            'links' => $this->links(),
            'description' => $this->seo->description($profile?->short_bio, $section['summary']),
            'schema' => $profile === null ? null : $this->seo->person($profile, $portrait, $section['url']),
            'copy' => [
                'title' => $this->pages->enteredText(Pages::ABOUT, 'title'),
                'epigraph' => $this->pages->enteredText(Pages::ABOUT, 'epigraph'),
                'expertise' => $this->pages->enteredText(Pages::ABOUT, 'expertise_heading'),
                'facts' => $this->pages->enteredText(Pages::ABOUT, 'facts_heading'),
                'contact' => $this->pages->enteredText(Pages::ABOUT, 'contact_heading'),
            ],
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
     * The profile's own contact details, as entered with the About page: an
     * empty field is left off the page (no site-wide fallback).
     *
     * @return array{email: string|null, note: string|null, phone: string|null, address: string|null}
     */
    public function contact(?Profile $profile): array
    {
        $value = fn (string $column): ?string => ($text = $this->own($profile, $column)) === '' ? null : $text;

        return [
            'email' => $value('contact_email_public'),
            'note' => $value('contact_note'),
            'phone' => $value('contact_phone'),
            'address' => $value('contact_address'),
        ];
    }

    /**
     * A profile value in the page's language only: what an editor entered there,
     * never the English shown in its place, so emptying a field takes it off.
     */
    private function own(?Profile $profile, string $column): string
    {
        if ($profile === null) {
            return '';
        }

        $value = $profile->isTranslatableAttribute($column)
            ? $profile->getTranslation($column, app()->getLocale(), false)
            : $profile->getAttribute($column);

        return is_string($value) ? trim($value) : '';
    }

    /**
     * A list column (expertise, key facts) in the page's language only.
     *
     * @return array<mixed>
     */
    private function ownList(?Profile $profile, string $column): array
    {
        $value = $profile?->getTranslation($column, app()->getLocale(), false);

        return is_array($value) ? $value : [];
    }

    /**
     * @return list<string>
     */
    private function expertise(?Profile $profile): array
    {
        $items = $this->ownList($profile, 'expertise');

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
        $items = $this->ownList($profile, 'key_facts');
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
