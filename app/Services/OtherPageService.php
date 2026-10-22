<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Sections;
use App\Models\OtherItem;
use App\Support\RichText;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * Assembles Other Information: items grouped by category, each linking on to
 * its destination or, when it has a body and no destination, to its own page.
 */
class OtherPageService
{
    public const string UNCATEGORISED_LABEL = 'Further information';

    public function __construct(
        private readonly OtherItemService $items,
        private readonly NavigationService $navigation,
    ) {}

    /**
     * @return array{
     *     section: array{key: string, label: string, title: string, url: string, route: string, placement: string, summary: string, active: bool},
     *     chapter: int,
     *     categories: list<array{label: string, anchor: string, items: Collection<int, OtherItem>}>
     * }
     */
    public function index(): array
    {
        $categories = [];

        foreach ($this->items->publishedByCategory() as $category => $items) {
            $label = $category === '' ? self::UNCATEGORISED_LABEL : $category;

            $categories[] = [
                'label' => $label,
                'anchor' => 'category-'.Str::slug($label),
                'items' => $items,
            ];
        }

        return [
            'section' => $this->navigation->section(Sections::OTHER, Sections::OTHER),
            'chapter' => $this->navigation->chapter(Sections::OTHER),
            'categories' => $categories,
        ];
    }

    /**
     * A published item that has its own page (a body and no destination link).
     */
    public function find(string $slug): ?OtherItem
    {
        $item = $this->items->findPublishedBySlug($slug);

        return $item !== null && $item->hasDetail() ? $item : null;
    }

    /**
     * @return array{
     *     section: array{key: string, label: string, title: string, url: string, route: string, placement: string, summary: string, active: bool},
     *     chapter: int,
     *     item: OtherItem,
     *     body: string
     * }
     */
    public function show(OtherItem $item): array
    {
        return [
            'section' => $this->navigation->section(Sections::OTHER, Sections::OTHER.'.show'),
            'chapter' => $this->navigation->chapter(Sections::OTHER),
            'item' => $item,
            'body' => RichText::sanitise($item->body),
        ];
    }

    public function detailUrl(OtherItem $item): string
    {
        return route(Sections::definitions()[Sections::OTHER]['route'].'.show', $item->slug);
    }
}
