<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\AdminNavigation;
use App\Constants\AdminUx;
use App\Constants\ContentTypes;
use App\Constants\DashboardGreeting;
use App\Constants\DateFormats;
use App\Constants\Permissions;
use App\Constants\StatusTone;
use App\Models\AuditLog;
use App\Models\User;
use App\Repositories\Contracts\AlbumRepositoryInterface;
use App\Repositories\Contracts\AwardRepositoryInterface;
use App\Repositories\Contracts\CommentaryRepositoryInterface;
use App\Repositories\Contracts\CvEntryRepositoryInterface;
use App\Repositories\Contracts\MediaRepositoryInterface;
use App\Repositories\Contracts\OtherItemRepositoryInterface;
use App\Repositories\Contracts\ProfileRepositoryInterface;
use App\Repositories\Contracts\PublicationRepositoryInterface;
use App\Repositories\Contracts\TeachingMaterialRepositoryInterface;
use App\Repositories\Contracts\TributeRepositoryInterface;
use App\Repositories\Contracts\VideoRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Figures for the dashboard home: published items per section, the moderation
 * queues and the latest audit entries. Nothing here is cached; the dashboard
 * is only seen by staff and must always be current.
 */
class DashboardService
{
    public const int RECENT_ACTIVITY_LIMIT = 10;

    public function __construct(
        private readonly ProfileRepositoryInterface $profiles,
        private readonly CvEntryRepositoryInterface $cvEntries,
        private readonly PublicationRepositoryInterface $publications,
        private readonly AwardRepositoryInterface $awards,
        private readonly VideoRepositoryInterface $videos,
        private readonly AlbumRepositoryInterface $albums,
        private readonly TeachingMaterialRepositoryInterface $teachingMaterials,
        private readonly CommentaryRepositoryInterface $commentaries,
        private readonly OtherItemRepositoryInterface $otherItems,
        private readonly TributeRepositoryInterface $tributes,
        private readonly TributeService $tributeService,
        private readonly CommunityService $community,
        private readonly ChatService $chat,
        private readonly AuditLogService $audit,
        private readonly MediaService $media,
        private readonly ActivityFeedService $activity,
        private readonly LaunchReadinessService $readiness,
        private readonly AuthenticationService $auth,
        private readonly AnalyticsReportService $analytics,
    ) {}

    /**
     * Published items per public section, keyed by admin navigation item.
     *
     * @return array<string, array{label: string, count: int, route: string, permission: string}>
     */
    public function publishedCounts(): array
    {
        $counts = [
            AdminNavigation::PROFILE => $this->profiles->countPublished(),
            AdminNavigation::CV => $this->cvEntries->countPublished(),
            AdminNavigation::PUBLICATIONS => $this->publications->countPublished(),
            AdminNavigation::AWARDS => $this->awards->countPublished(),
            AdminNavigation::VIDEOS => $this->videos->countPublished(),
            AdminNavigation::ALBUMS => $this->albums->countPublished(),
            AdminNavigation::TEACHING => $this->teachingMaterials->countPublished(),
            AdminNavigation::COMMENTARY => $this->commentaries->countPublished(),
            AdminNavigation::OTHER => $this->otherItems->countPublished(),
            AdminNavigation::TRIBUTES => $this->tributes->countPublished(),
        ];

        $cards = [];

        foreach ($counts as $key => $count) {
            $item = AdminNavigation::get($key);

            $cards[$key] = [
                // The tributes item is named for its task ("Review tributes"); the figure counts tributes.
                'label' => $key === AdminNavigation::TRIBUTES ? 'Tributes' : $item['label'],
                'count' => $count,
                'route' => $item['route'],
                'permission' => $item['permission'],
            ];
        }

        return $cards;
    }

    /**
     * The moderation queues with their current size, in the order they are shown.
     *
     * @return array<string, array{label: string, count: int, route: string, permission: string, tone: StatusTone, noun: string}>
     */
    public function queues(): array
    {
        return [
            AdminNavigation::TRIBUTES => $this->queue(AdminNavigation::TRIBUTES, 'Tributes pending review', $this->tributeService->countPending(), 'tribute'),
            AdminNavigation::APPLICATIONS => $this->queue(AdminNavigation::APPLICATIONS, 'Community applications pending', $this->community->countPending(), 'application'),
            AdminNavigation::CHAT_REPORTS => $this->queue(AdminNavigation::CHAT_REPORTS, 'Chat reports open', $this->chat->countOpenReports(), 'report'),
        ];
    }

    /**
     * Queues with at least one item waiting, limited to those the user may act on.
     *
     * @return array<string, array{label: string, count: int, route: string, permission: string, tone: StatusTone, noun: string}>
     */
    public function needsAttention(?User $user = null): array
    {
        return array_filter(
            $this->queues(),
            fn (array $queue): bool => $queue['count'] > 0 && ($user === null || $user->can($queue['permission'])),
        );
    }

    /**
     * The "Needs your attention" cards: the moderation queues, images without
     * alt text and drafts nobody has touched for a while. Only cards with
     * something waiting, and only those the user may act on.
     *
     * @return list<array{key: string, title: string, count: int, detail: string, action: string, url: string, items: list<array{label: string, meta: string, url: string}>}>
     */
    public function attentionCards(User $user, ?Carbon $now = null): array
    {
        $now ??= Carbon::now();
        $cards = [];

        foreach ($this->queues() as $key => $queue) {
            if ($queue['count'] === 0 || ! $user->can($queue['permission'])) {
                continue;
            }

            $cards[] = $this->card($key, $queue['label'], $queue['count'], match ($key) {
                AdminNavigation::TRIBUTES => 'Visitors have left tributes. Read each one, then approve it for the public page or decline it.',
                AdminNavigation::APPLICATIONS => 'People have asked to join the Scientific Community. Approve them to open the members\' chat.',
                default => 'Members have reported chat messages. Look at each and keep or remove the message.',
            }, match ($key) {
                AdminNavigation::TRIBUTES => 'Review tributes',
                AdminNavigation::APPLICATIONS => 'Review applications',
                default => 'Open reports',
            }, route($queue['route']));
        }

        if (! $user->can(Permissions::MANAGE_CONTENT)) {
            return $cards;
        }

        $missingAlt = $this->media->countMissingAltText();

        if ($missingAlt > 0) {
            $cards[] = $this->card('missing-alt', 'Images without alt text', $missingAlt,
                'Alt text describes a photo for people who cannot see it. Each image needs a sentence before its page can be published.',
                'Add alt text', route(AdminNavigation::routeFor(AdminNavigation::MEDIA_LIBRARY), [MediaRepositoryInterface::FILTER_MISSING_ALT => 1]));
        }

        $drafts = $this->staleDrafts($now);

        if ($drafts['count'] > 0) {
            $card = $this->card('stale-drafts', 'Drafts waiting more than '.AdminUx::STALE_DRAFT_DAYS.' days', $drafts['count'],
                'These were started but never published. Finish and publish them, or move them to the trash.',
                'Open the oldest', $drafts['items'][0]['url']);
            $card['items'] = $drafts['items'];
            $cards[] = $card;
        }

        return $cards;
    }

    /**
     * Drafts last changed more than AdminUx::STALE_DRAFT_DAYS ago, oldest first, across every content type.
     *
     * @return array{count: int, items: list<array{label: string, meta: string, url: string}>}
     */
    public function staleDrafts(?Carbon $now = null): array
    {
        $before = ($now ?? Carbon::now())->copy()->subDays(AdminUx::STALE_DRAFT_DAYS);
        $count = 0;
        $found = [];

        foreach (ContentTypes::services() as $type => $service) {
            if ($type === ContentTypes::PROFILE) {
                continue;
            }

            $service = app($service);
            $count += $service->countStaleDrafts($before);

            foreach ($service->staleDrafts($before, AdminUx::ATTENTION_ITEMS) as $record) {
                $found[] = ['type' => $type, 'record' => $record];
            }
        }

        usort($found, fn (array $a, array $b): int => $a['record']->updated_at <=> $b['record']->updated_at);

        $items = array_map(function (array $entry): array {
            $record = $entry['record'];
            $label = $record->getAttribute('title') ?? $record->getAttribute('name') ?? $record->getAttribute('quote') ?? ContentTypes::get($entry['type'])['singular'];

            return [
                'label' => Str::limit((string) $label, 80),
                'meta' => ContentTypes::get($entry['type'])['singular'].' · last changed '.$record->updated_at->diffForHumans(),
                'url' => route(ContentTypes::editRoute($entry['type']), [ContentTypes::routeParameter($entry['type']) => $record->getKey()]),
            ];
        }, array_slice($found, 0, AdminUx::ATTENTION_ITEMS));

        return ['count' => $count, 'items' => $items];
    }

    /**
     * Shortcuts to the things editors do most.
     *
     * @return list<array{key: string, label: string, hint: string, icon: string, url: string}>
     */
    public function quickActions(User $user): array
    {
        if (! $user->can(Permissions::MANAGE_CONTENT)) {
            return [];
        }

        return [
            ['key' => 'new-publication', 'label' => 'New publication', 'hint' => 'A paper, book or chapter, with its PDF', 'icon' => AdminUx::icon(AdminNavigation::PUBLICATIONS), 'url' => route(ContentTypes::createRoute(ContentTypes::PUBLICATION))],
            ['key' => 'new-commentary', 'label' => 'New commentary', 'hint' => 'An essay or note for the Commentary Corner', 'icon' => AdminUx::icon(AdminNavigation::COMMENTARY), 'url' => route(ContentTypes::createRoute(ContentTypes::COMMENTARY))],
            ['key' => 'upload-photos', 'label' => 'Upload photos', 'hint' => 'Into an album, then add captions', 'icon' => AdminUx::icon(AdminNavigation::ALBUMS), 'url' => route(AdminNavigation::routeFor(AdminNavigation::MEDIA_LIBRARY)).'#upload'],
            ['key' => 'new-cv-entry', 'label' => 'Add CV entry', 'hint' => 'A post, degree or appointment', 'icon' => AdminUx::icon(AdminNavigation::CV), 'url' => route(ContentTypes::createRoute(ContentTypes::CV_ENTRY))],
        ];
    }

    /**
     * @return Collection<int, AuditLog>
     */
    public function recentActivity(int $limit = self::RECENT_ACTIVITY_LIMIT): Collection
    {
        return $this->audit->recent($limit);
    }

    /**
     * The greeting at the top of the dashboard: a salutation for the hour and the date in small caps.
     *
     * @return array{salutation: string, date: string, datetime: string}
     */
    public function greeting(?Carbon $now = null): array
    {
        $now ??= Carbon::now();

        return [
            'salutation' => DashboardGreeting::salutation((int) $now->format('G')),
            'date' => $now->format(DateFormats::GREETING),
            'datetime' => $now->format(DateFormats::MACHINE),
        ];
    }

    /**
     * Everything the dashboard home renders, in one call.
     *
     * @return array{greeting: array{salutation: string, date: string, datetime: string}, published: array<string, array{label: string, count: int, route: string, permission: string}>, queues: array<string, array{label: string, count: int, route: string, permission: string, tone: StatusTone, noun: string}>, attention: array<string, array{label: string, count: int, route: string, permission: string, tone: StatusTone, noun: string}>, activity: Collection<int, AuditLog>}
     */
    public function summary(?User $user = null): array
    {
        $queues = $this->queues();
        $canManageContent = $user === null || $user->can(Permissions::MANAGE_CONTENT);

        return [
            'cards' => $user === null ? [] : $this->attentionCards($user),
            'actions' => $user === null ? [] : $this->quickActions($user),
            'checklist' => $canManageContent ? $this->readiness->checklist() : null,
            'feed' => $this->activity->recent(),
            'greeting' => $this->greeting(),
            'twoFactorReminder' => $user !== null && $this->auth->shouldRemindTwoFactor($user),
            'audience' => $user !== null && $user->can(Permissions::VIEW_ANALYTICS) ? $this->analytics->audience() : null,
            'published' => $this->publishedCounts(),
            'queues' => $queues,
            'attention' => array_filter(
                $queues,
                fn (array $queue): bool => $queue['count'] > 0 && ($user === null || $user->can($queue['permission'])),
            ),
            'activity' => $this->recentActivity(),
        ];
    }

    /**
     * @return array{key: string, title: string, count: int, detail: string, action: string, url: string, items: list<array{label: string, meta: string, url: string}>}
     */
    private function card(string $key, string $title, int $count, string $detail, string $action, string $url): array
    {
        return ['key' => $key, 'title' => $title, 'count' => $count, 'detail' => $detail, 'action' => $action, 'url' => $url, 'items' => []];
    }

    /**
     * @return array{label: string, count: int, route: string, permission: string, tone: StatusTone, noun: string}
     */
    private function queue(string $key, string $label, int $count, string $noun): array
    {
        $item = AdminNavigation::get($key);

        return [
            'label' => $label,
            'count' => $count,
            'route' => $item['route'],
            'permission' => $item['permission'],
            'tone' => $count > 0 ? StatusTone::Bronze : StatusTone::Cypress,
            'noun' => $noun,
        ];
    }
}
