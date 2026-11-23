<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\AdminNavigation;
use App\Constants\ContentStatus;
use App\Constants\ContentTypes;
use App\Constants\Pages;
use App\Constants\StatusTone;
use App\Models\Tribute;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * The records a page lists (its publications, awards, videos...), for the page's
 * own editor, so the editor shows every part of the page: its words above and
 * its entries here, in the order the page shows them, each opening its form.
 */
class PageRecordsService
{
    /** More than this and the editor links to the full list instead of showing every row. */
    public const int LIMIT = 200;

    public function __construct(
        private readonly ProfileService $profiles,
        private readonly TributeService $tributes,
    ) {}

    /**
     * @return array{type: string, plural: string, singular: string, indexUrl: string, createUrl: ?string, total: int, rows: list<array{title: string, year: ?string, statusLabel: string, tone: StatusTone, editUrl: string}>}|null
     */
    public function for(string $page): ?array
    {
        return match (true) {
            $page === Pages::ABOUT => $this->profile(),
            $page === Pages::TRIBUTES => $this->approvedTributes(),
            isset(Pages::RECORDS[$page]) => $this->records(Pages::RECORDS[$page]),
            default => null,
        };
    }

    /**
     * @return array{type: string, plural: string, singular: string, indexUrl: string, createUrl: ?string, total: int, rows: list<array{title: string, year: ?string, statusLabel: string, tone: StatusTone, editUrl: string}>}
     */
    private function records(string $type): array
    {
        /** @var AbstractContentService<Model> $service */
        $service = app(ContentTypes::services()[$type]);
        $records = $service->search(perPage: self::LIMIT);
        $definition = ContentTypes::get($type);

        return [
            'type' => $type,
            'plural' => __($definition['plural']),
            'singular' => __($definition['singular']),
            'indexUrl' => route(ContentTypes::indexRoute($type)),
            'createUrl' => route(ContentTypes::createRoute($type)),
            'total' => $records->total(),
            'rows' => array_map(function (Model $record) use ($type, $definition): array {
                $status = $record->getAttribute('status') instanceof ContentStatus ? $record->getAttribute('status') : ContentStatus::Draft;

                return [
                    'title' => $this->title($record, $definition['singular']),
                    'year' => $this->year($record),
                    'statusLabel' => $status->label(),
                    'tone' => StatusTone::forContent($status),
                    'editUrl' => route(ContentTypes::editRoute($type), [$record]),
                ];
            }, $records->items()),
        ];
    }

    /**
     * The About page's biography, portrait and facts live in one profile.
     *
     * @return array{type: string, plural: string, singular: string, indexUrl: string, createUrl: ?string, total: int, rows: list<array{title: string, year: ?string, statusLabel: string, tone: StatusTone, editUrl: string}>}
     */
    private function profile(): array
    {
        $profile = $this->profiles->get();
        $status = $profile?->getAttribute('status') instanceof ContentStatus ? $profile->getAttribute('status') : ContentStatus::Draft;
        $url = route(ContentTypes::editRoute(ContentTypes::PROFILE));

        return [
            'type' => ContentTypes::PROFILE,
            'plural' => __('About & profile'),
            'singular' => __('Profile'),
            'indexUrl' => $url,
            'createUrl' => null,
            'total' => 1,
            'rows' => [[
                'title' => filled($profile?->getAttribute('name')) ? (string) $profile->getAttribute('name') : __('Profile'),
                'year' => null,
                'statusLabel' => $status->label(),
                'tone' => StatusTone::forContent($status),
                'editUrl' => $url,
            ]],
        ];
    }

    /**
     * The tributes the page shows (approved), each opening the review screen.
     *
     * @return array{type: string, plural: string, singular: string, indexUrl: string, createUrl: ?string, total: int, rows: list<array{title: string, year: ?string, statusLabel: string, tone: StatusTone, editUrl: string}>}
     */
    private function approvedTributes(): array
    {
        $tributes = $this->tributes->approved();
        $url = route(AdminNavigation::routeFor(AdminNavigation::TRIBUTES));

        return [
            'type' => 'tributes',
            'plural' => __('Tributes'),
            'singular' => __('Tribute'),
            'indexUrl' => $url,
            'createUrl' => null,
            'total' => $tributes->count(),
            'rows' => $tributes->take(self::LIMIT)->map(fn (Tribute $tribute): array => [
                'title' => Str::limit(trim(((string) $tribute->name).' — '.strip_tags((string) $tribute->message)), 90),
                'year' => $tribute->created_at?->format('Y'),
                'statusLabel' => $tribute->status->label(),
                'tone' => StatusTone::forTribute($tribute->status),
                'editUrl' => $url,
            ])->values()->all(),
        ];
    }

    /**
     * The year a record is known by: its year, its first year, or the year of its date.
     */
    private function year(Model $record): ?string
    {
        foreach (['year', 'start_year', 'date', 'material_date'] as $column) {
            $value = $record->getAttribute($column);

            if ($value instanceof DateTimeInterface) {
                return $value->format('Y');
            }

            if (filled($value)) {
                return (string) $value;
            }
        }

        return null;
    }

    private function title(Model $record, string $singular): string
    {
        $title = $record->getAttribute('title') ?? $record->getAttribute('name') ?? $record->getAttribute('heading');

        return filled($title) ? Str::limit(strip_tags((string) $title), 90) : __($singular);
    }
}
