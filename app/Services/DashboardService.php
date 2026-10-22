<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\AdminNavigation;
use App\Constants\DashboardGreeting;
use App\Constants\DateFormats;
use App\Constants\StatusTone;
use App\Models\AuditLog;
use App\Models\User;
use App\Repositories\Contracts\AlbumRepositoryInterface;
use App\Repositories\Contracts\AwardRepositoryInterface;
use App\Repositories\Contracts\CommentaryRepositoryInterface;
use App\Repositories\Contracts\CvEntryRepositoryInterface;
use App\Repositories\Contracts\OtherItemRepositoryInterface;
use App\Repositories\Contracts\ProfileRepositoryInterface;
use App\Repositories\Contracts\PublicationRepositoryInterface;
use App\Repositories\Contracts\TeachingMaterialRepositoryInterface;
use App\Repositories\Contracts\TributeRepositoryInterface;
use App\Repositories\Contracts\VideoRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

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
                'label' => $item['label'],
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

        return [
            'greeting' => $this->greeting(),
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
