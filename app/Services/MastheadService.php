<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\AdminNavigation;
use App\Constants\Chapters;
use App\Constants\DateFormats;
use App\Constants\Masthead;
use App\Constants\Pages;
use App\Models\Album;
use App\Models\Profile;
use App\Models\Publication;
use App\Models\Tribute;
use App\Models\User;
use App\Repositories\Contracts\ChapterRepositoryInterface;
use App\Support\Initials;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Builds the masthead: the five chapters with their links (from NavigationService,
 * so names and active states stay in one place), the action links a visitor may
 * see, the featured item in each panel and the utility line. The featured items
 * come from the content services, which already cache them for the public site.
 *
 * @phpstan-import-type NavItem from NavigationService
 *
 * @phpstan-type Link array{key: string, label: string, description: string, url: string, active: bool}
 * @phpstan-type Featured array{kind: string, label: string, title: string, meta: string|null, quote: string|null, url: string, media: Media|null}
 * @phpstan-type AccountMenu array{name: string, initials: string, role: string, items: list<array{key: string, label: string, url: string, icon: string}>}
 * @phpstan-type Chapter array{key: string, numeral: string, label: string, strap: string, panelId: string, buttonId: string, items: list<Link>, active: bool, featured: Featured|null}
 */
class MastheadService
{
    public const int QUOTE_LENGTH = 120;

    public function __construct(
        private readonly ChapterRepositoryInterface $chapters,
        private readonly NavigationService $navigation,
        private readonly TypesetterService $typesetter,
        private readonly ProfileService $profiles,
        private readonly PublicationService $publications,
        private readonly AlbumService $albums,
        private readonly TributeService $tributes,
        private readonly AuthenticationService $auth,
        private readonly PageContentService $pages,
    ) {}

    /**
     * The chapters in masthead order, with their links, active state and featured item.
     *
     * @return list<Chapter>
     */
    public function chapters(?string $currentRoute = null, ?User $user = null): array
    {
        $chapters = [];
        $position = 0;
        $definitions = $this->chapters->all();

        // Header & footer (Admin › Pages) sets the order, names and straps; the chapters' sections stay fixed.
        foreach ($this->pages->items(Pages::NAVIGATION, 'chapters') as $copy) {
            $key = $copy['key'];
            $definition = $definitions[$key];
            $items = $this->links($definition, $currentRoute, $user);

            $chapters[] = [
                'key' => $key,
                'numeral' => $this->typesetter->roman(++$position),
                'label' => $copy['label'],
                'strap' => $copy['strap'],
                'panelId' => Masthead::chapterPanelId($key),
                'buttonId' => Masthead::chapterButtonId($key),
                'items' => $items,
                'active' => array_any($items, fn (array $item): bool => $item['active']),
                'featured' => $definition['featured'] === null ? null : $this->featured($definition['featured']),
            ];
        }

        return $chapters;
    }

    /**
     * The chapter the current route belongs to, or null (home, search, sign-in).
     */
    public function activeChapter(?string $currentRoute): ?string
    {
        foreach ($this->navigation->all($currentRoute) as $item) {
            if ($item['active']) {
                return $this->chapters->chapterOf($item['key']);
            }
        }

        return null;
    }

    /**
     * The utility line: the dedication on the left and, on the right, the sign-in
     * for guests, the chat for members or the account page for other signed-in users.
     *
     * @return array{line: string, link: array{label: string, url: string}}
     */
    public function utility(?User $user): array
    {
        $link = match (true) {
            $user === null => ['label' => __(Masthead::SIGN_IN), 'url' => route('login')],
            $user->canAccessChat() => ['label' => __(Masthead::OPEN_CHAT), 'url' => route('community.chat')],
            default => ['label' => __(Masthead::ACCOUNT), 'url' => route(AdminNavigation::ACCOUNT_ROUTE)],
        };

        return ['line' => $this->pages->text(Pages::NAVIGATION, 'utility_line'), 'link' => $link];
    }

    /**
     * The signed-in user's account menu (null for visitors, who see a quiet Sign in link):
     * the name, initials and role, then Your account, Community chat for those who may use
     * it and Dashboard for staff. The theme toggle and Sign out are drawn by the view.
     *
     * @return AccountMenu|null
     */
    public function account(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        $items = [['key' => 'account', 'label' => __(Masthead::ACCOUNT), 'url' => route(AdminNavigation::ACCOUNT_ROUTE), 'icon' => 'user']];

        if ($user->canAccessChat()) {
            $items[] = ['key' => 'chat', 'label' => __(Masthead::ACCOUNT_MENU_CHAT), 'url' => route('community.chat'), 'icon' => 'users'];
        }

        if ($this->auth->isStaff($user)) {
            $items[] = ['key' => 'dashboard', 'label' => __(Masthead::ACCOUNT_MENU_DASHBOARD), 'url' => route(AdminNavigation::DASHBOARD_ROUTE), 'icon' => 'panel'];
        }

        return [
            'name' => $user->name,
            'initials' => Initials::of($user->name),
            'role' => $user->primaryRoleLabel(),
            'items' => $items,
        ];
    }

    /**
     * The featured item of a kind, or null when nothing is published for it.
     *
     * @return Featured|null
     */
    public function featured(string $kind): ?array
    {
        $label = isset(Chapters::featuredLabels()[$kind])
            ? ($this->pages->keyed(Pages::NAVIGATION, 'featured_labels')[$kind]['label'] ?? __(Chapters::featuredLabels()[$kind]))
            : null;

        if ($label === null) {
            return null;
        }

        return match ($kind) {
            Chapters::FEATURED_PORTRAIT => $this->portrait($label),
            Chapters::FEATURED_PUBLICATION => $this->publication($label),
            Chapters::FEATURED_ALBUM => $this->album($label),
            Chapters::FEATURED_TRIBUTE => $this->tribute($label),
            default => null,
        };
    }

    /**
     * @param  array{label: string, strap: string, sections: list<string>, actions: list<string>, featured: string|null}  $definition
     * @return list<Link>
     */
    private function links(array $definition, ?string $currentRoute, ?User $user): array
    {
        $links = [];

        $sections = $this->pages->keyed(Pages::NAVIGATION, 'sections');
        $actions = $this->pages->keyed(Pages::NAVIGATION, 'actions');

        foreach ($definition['sections'] as $section) {
            $item = $this->navigation->section($section, $currentRoute);

            $links[] = [
                'key' => $item['key'],
                'label' => $item['label'],
                'description' => $sections[$section]['line'] ?? $this->translated($this->chapters->description($section)) ?? $item['summary'],
                'url' => $item['url'],
                'active' => $item['active'],
            ];
        }

        foreach ($definition['actions'] as $key) {
            $action = $this->chapters->action($key);

            if ($action === null || ! $this->sees($action['audience'], $user)) {
                continue;
            }

            $links[] = [
                'key' => $key,
                'label' => $actions[$key]['label'] ?? __($action['label']),
                'description' => $actions[$key]['line'] ?? __($action['description']),
                'url' => route($action['route']),
                'active' => false, // Actions are calls to action, never the current page: the section stays the one current item.
            ];
        }

        return $links;
    }

    private function translated(?string $text): ?string
    {
        return $text === null ? null : __($text);
    }

    private function sees(string $audience, ?User $user): bool
    {
        return match ($audience) {
            Chapters::AUDIENCE_GUEST => $user === null,
            Chapters::AUDIENCE_MEMBER => $user?->canAccessChat() ?? false,
            default => true,
        };
    }

    /**
     * @return Featured|null
     */
    private function portrait(string $label): ?array
    {
        $profile = $this->profiles->get();

        if (! $profile instanceof Profile) {
            return null;
        }

        return [
            'kind' => Chapters::FEATURED_PORTRAIT,
            'label' => $label,
            'title' => $profile->name,
            'meta' => $profile->title ?: null,
            'quote' => null,
            'url' => route('about'),
            'media' => $profile->portrait(),
        ];
    }

    /**
     * @return Featured|null
     */
    private function publication(string $label): ?array
    {
        $publication = $this->publications->latest()->first();

        if (! $publication instanceof Publication) {
            return null;
        }

        return [
            'kind' => Chapters::FEATURED_PUBLICATION,
            'label' => $label,
            'title' => $publication->title,
            'meta' => implode(DateFormats::META_SEPARATOR, array_filter([$publication->type?->label(), $publication->year ? (string) $publication->year : null])) ?: null,
            'quote' => null,
            'url' => route('publications').'#publication-'.$publication->slug,
            'media' => null,
        ];
    }

    /**
     * @return Featured|null
     */
    private function album(string $label): ?array
    {
        $album = $this->albums->newest();

        if (! $album instanceof Album) {
            return null;
        }

        $count = $album->photos()->count();

        return [
            'kind' => Chapters::FEATURED_ALBUM,
            'label' => $label,
            'title' => $album->title,
            'meta' => $count > 0 ? trans_choice('{1} :count photograph|[2,*] :count photographs', $count, ['count' => app(LocaleService::class)->digits($count)]) : null,
            'quote' => null,
            'url' => route('pictures.show', $album),
            'media' => $album->cover(),
        ];
    }

    /**
     * @return Featured|null
     */
    private function tribute(string $label): ?array
    {
        $tribute = $this->tributes->featured();

        if (! $tribute instanceof Tribute) {
            return null;
        }

        return [
            'kind' => Chapters::FEATURED_TRIBUTE,
            'label' => $label,
            'title' => $tribute->name,
            'meta' => $tribute->relationship?->label(),
            'quote' => Str::limit(trim((string) $tribute->message), self::QUOTE_LENGTH, '…'),
            'url' => route('tributes'),
            'media' => null,
        ];
    }
}
