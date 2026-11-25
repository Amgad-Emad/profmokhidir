<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\AdminNavigation;
use App\Constants\ContentStatus;
use App\Constants\ContentTypes;
use App\Constants\LaunchChecklist;
use App\Constants\Locales;
use App\Constants\Placeholders;
use App\Repositories\Contracts\ContentRepositoryInterface;

/**
 * The dashboard's "Before launch" checklist, computed from the database every
 * time: the portrait is set, contact details exist, the profile is published,
 * no placeholder copy from the seeders remains (counted per section), every
 * section has at least one published item, and no content still waits for its
 * Arabic translation. Each check links to its fix. Labels are in the current
 * language.
 *
 * @phpstan-type Check array{key: string, label: string, done: bool, detail: string, url: ?string, action: string}
 */
class LaunchReadinessService
{
    public function __construct(
        private readonly ProfileService $profiles,
    ) {}

    /**
     * Groups of checks, in the order the checklist shows them.
     *
     * @return array{checks: list<Check>, done: int, total: int}
     */
    public function checklist(): array
    {
        $checks = array_merge(
            [$this->portrait(), $this->contact(), $this->profilePublished()],
            $this->placeholders(),
            $this->publishedSections(),
            [$this->translations()],
        );

        $done = count(array_filter($checks, fn (array $check): bool => $check['done']));

        return ['checks' => $checks, 'done' => $done, 'total' => count($checks)];
    }

    /**
     * @return Check
     */
    public function portrait(): array
    {
        $profile = $this->profiles->current();
        $done = $profile !== null && $profile->portrait() !== null;

        return $this->check(LaunchChecklist::PORTRAIT, __('Portrait is set'), $done,
            $done ? __('The About page opens with his portrait.') : __('The About page has no portrait yet.'),
            $this->profileUrl().'#section-portrait', __('Add the portrait'));
    }

    /**
     * @return Check
     */
    public function contact(): array
    {
        // The About page shows only the contact details entered with it (Admin › Pages › About).
        $profile = $this->profiles->current();
        $done = filled($profile?->contact_email_public) || filled($profile?->contact_phone);

        return $this->check(LaunchChecklist::CONTACT, __('Contact details are set'), $done,
            $done ? __('Visitors have an address to write to.') : __('There is no public contact email yet.'),
            $this->profileUrl().'#section-contact', __('Add contact details'));
    }

    /**
     * @return Check
     */
    public function profilePublished(): array
    {
        $profile = $this->profiles->current();
        $done = $profile !== null && $profile->status === ContentStatus::Published;

        return $this->check(LaunchChecklist::PROFILE_PUBLISHED, __('The About page is published'), $done,
            $done ? __('The biography is public.') : __('The profile is still a draft, so the About page is empty.'),
            $this->profileUrl(), __('Open the profile'));
    }

    /**
     * One check per section still holding placeholder copy (and one "done"
     * line when none is left).
     *
     * @return list<Check>
     */
    public function placeholders(): array
    {
        $checks = [];

        foreach (LaunchChecklist::placeholderTypes() as $type) {
            $count = app(ContentTypes::services()[$type])->countContaining(Placeholders::MARKER);

            if ($count === 0) {
                continue;
            }

            $definition = ContentTypes::get($type);
            $noun = $this->lowerNoun(__($count === 1 ? $definition['singular'] : $definition['plural']));

            $checks[] = $this->check(LaunchChecklist::PLACEHOLDERS.'-'.$type, __('Replace the sample text in :section', ['section' => __($definition['plural'])]), false,
                $type === ContentTypes::PROFILE
                    ? __('The profile still holds sample text from the set-up.')
                    : trans_choice('{1} :count :noun still holds sample text from the set-up.|[2,*] :count :noun still hold sample text from the set-up.', $count, [
                        'count' => $this->digits($count), 'noun' => $noun, 'section' => __($definition['plural']),
                    ]),
                $type === ContentTypes::PROFILE ? $this->profileUrl() : route(ContentTypes::indexRoute($type), [ContentRepositoryInterface::FILTER_PLACEHOLDER => 1]),
                __('Show them'));
        }

        if ($checks === []) {
            $checks[] = $this->check(LaunchChecklist::PLACEHOLDERS, __('No sample text is left'), true, __('Every section holds real copy.'), null, '');
        }

        return $checks;
    }

    /**
     * @return list<Check>
     */
    public function publishedSections(): array
    {
        $checks = [];

        foreach (LaunchChecklist::sections() as $nav => $type) {
            $count = app(ContentTypes::services()[$type])->countPublished();
            $label = AdminNavigation::get($nav)['label'];
            $definition = ContentTypes::get($type);

            $checks[] = $this->check(LaunchChecklist::PUBLISHED.'-'.$nav, __(':section has something published', ['section' => $label]), $count > 0,
                $count > 0 ? __(':count published.', ['count' => $this->digits($count)]) : __('Nothing is published here yet, so visitors see an empty page.'),
                route($count > 0 ? ContentTypes::indexRoute($type) : ContentTypes::createRoute($type)),
                $count > 0 ? __('Open') : __('Add the first :item', ['item' => mb_strtolower(__($definition['singular']))]));
        }

        return $checks;
    }

    /**
     * Records with English copy still waiting for Arabic, summed over every
     * content type; the fix opens the type with the most of them, filtered to
     * those records. Done when nothing is left to translate.
     *
     * @return Check
     */
    public function translations(): array
    {
        $counts = [];

        foreach (LaunchChecklist::translationTypes() as $type) {
            $counts[$type] = app(ContentTypes::services()[$type])->countNeedingTranslation(Locales::AR);
        }

        $total = array_sum($counts);
        arsort($counts);
        $most = (string) array_key_first($counts);

        $url = match (true) {
            $total === 0 => null,
            $most === ContentTypes::PROFILE => $this->profileUrl(),
            default => route(ContentTypes::indexRoute($most), ['filters' => [ContentRepositoryInterface::FILTER_NEEDS_TRANSLATION => 1]]),
        };

        return $this->check(LaunchChecklist::TRANSLATION, __('Needs Arabic translation'), $total === 0,
            $total === 0
                ? __('Every item has its Arabic text.')
                : trans_choice('{1} :count item is shown in English on the Arabic pages until it is translated.|[2,*] :count items are shown in English on the Arabic pages until they are translated.', $total, ['count' => $this->digits($total)]),
            $url,
            $total === 0 ? '' : __('Translate :section', ['section' => __(ContentTypes::get($most)['plural'])]));
    }

    private function digits(int $count): string
    {
        return app(LocaleService::class)->digits($count);
    }

    /**
     * "Publications" → "publications", but "CV entries" keeps its capitals.
     */
    private function lowerNoun(string $noun): string
    {
        return preg_match('/^[A-Z]{2}/', $noun) === 1 ? $noun : lcfirst($noun);
    }

    private function profileUrl(): string
    {
        return route(ContentTypes::indexRoute(ContentTypes::PROFILE));
    }

    /**
     * @return Check
     */
    private function check(string $key, string $label, bool $done, string $detail, ?string $url, string $action): array
    {
        return ['key' => $key, 'label' => $label, 'done' => $done, 'detail' => $detail, 'url' => $url, 'action' => $action];
    }
}
