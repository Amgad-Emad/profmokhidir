<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Sections;
use App\Models\Award;
use Illuminate\Database\Eloquent\Collection;

/**
 * Assembles the roll of honour from the published awards in display order.
 */
class AwardsPageService
{
    public function __construct(
        private readonly AwardService $awards,
        private readonly NavigationService $navigation,
    ) {}

    /**
     * @return array{
     *     section: array{key: string, label: string, title: string, url: string, route: string, placement: string, summary: string, active: bool},
     *     chapter: int,
     *     awards: Collection<int, Award>
     * }
     */
    public function data(): array
    {
        return [
            'section' => $this->navigation->section(Sections::AWARDS, Sections::AWARDS),
            'chapter' => $this->navigation->chapter(Sections::AWARDS),
            'awards' => $this->awards->published(),
        ];
    }
}
