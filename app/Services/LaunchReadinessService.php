<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\AdminNavigation;
use App\Constants\ContentStatus;
use App\Constants\ContentTypes;
use App\Constants\LaunchChecklist;
use App\Constants\Placeholders;
use App\Repositories\Contracts\ContentRepositoryInterface;

/**
 * The dashboard's "Before launch" checklist, computed from the database every
 * time: the portrait is set, contact details exist, the profile is published,
 * no placeholder copy from the seeders remains (counted per section), and every
 * section has at least one published item. Each check links to its fix.
 *
 * @phpstan-type Check array{key: string, label: string, done: bool, detail: string, url: ?string, action: string}
 */
class LaunchReadinessService
{
    public function __construct(
        private readonly ProfileService $profiles,
        private readonly SettingService $settings,
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

        return $this->check(LaunchChecklist::PORTRAIT, 'Portrait is set', $done,
            $done ? 'The About page opens with his portrait.' : 'The About page has no portrait yet.',
            $this->profileUrl().'#section-portrait', 'Add the portrait');
    }

    /**
     * @return Check
     */
    public function contact(): array
    {
        $profile = $this->profiles->current();
        $site = $this->settings->contact();
        $done = filled($profile?->contact_email_public) || filled($site['email']);

        return $this->check(LaunchChecklist::CONTACT, 'Contact details are set', $done,
            $done ? 'Visitors have an address to write to.' : 'There is no public contact email yet.',
            $this->profileUrl().'#section-contact', 'Add contact details');
    }

    /**
     * @return Check
     */
    public function profilePublished(): array
    {
        $profile = $this->profiles->current();
        $done = $profile !== null && $profile->status === ContentStatus::Published;

        return $this->check(LaunchChecklist::PROFILE_PUBLISHED, 'The About page is published', $done,
            $done ? 'The biography is public.' : 'The profile is still a draft, so the About page is empty.',
            $this->profileUrl(), 'Open the profile');
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
            $noun = $this->lowerNoun($count === 1 ? $definition['singular'] : $definition['plural']);

            $checks[] = $this->check(LaunchChecklist::PLACEHOLDERS.'-'.$type, 'Replace the sample text in '.$definition['plural'], false,
                $type === ContentTypes::PROFILE ? 'The profile still holds sample text from the set-up.' : sprintf('%d %s still hold sample text from the set-up.', $count, $noun),
                $type === ContentTypes::PROFILE ? $this->profileUrl() : route(ContentTypes::indexRoute($type), [ContentRepositoryInterface::FILTER_PLACEHOLDER => 1]),
                'Show them');
        }

        if ($checks === []) {
            $checks[] = $this->check(LaunchChecklist::PLACEHOLDERS, 'No sample text is left', true, 'Every section holds real copy.', null, '');
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

            $checks[] = $this->check(LaunchChecklist::PUBLISHED.'-'.$nav, $label.' has something published', $count > 0,
                $count > 0 ? sprintf('%d published.', $count) : 'Nothing is published here yet, so visitors see an empty page.',
                route($count > 0 ? ContentTypes::indexRoute($type) : ContentTypes::createRoute($type)),
                $count > 0 ? 'Open' : 'Add the first '.strtolower($definition['singular']));
        }

        return $checks;
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
