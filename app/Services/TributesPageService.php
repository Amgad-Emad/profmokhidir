<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\PageSizes;
use App\Constants\Sections;
use App\Constants\SocialCards;
use App\Constants\Taglines;
use App\Constants\TributeRelationship;
use App\Models\Tribute;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Assembles the public Tributes pages: the paginated wall of approved tributes
 * with its share card, the submission form and the calm confirmation.
 *
 * @phpstan-type NavItem array{key: string, label: string, title: string, url: string, route: string, placement: string, summary: string, active: bool}
 */
class TributesPageService
{
    public const string ROUTE_INDEX = 'tributes';

    public const string ROUTE_CREATE = 'tributes.create';

    public const string ROUTE_STORE = 'tributes.store';

    public const string ROUTE_THANKS = 'tributes.thanks';

    public function __construct(
        private readonly TributeService $tributes,
        private readonly NavigationService $navigation,
    ) {}

    /**
     * @return array{
     *     section: NavItem,
     *     tributes: LengthAwarePaginator<int, Tribute>,
     *     copy: array{label: string, heading: string, leaveTribute: string},
     *     shareImage: string,
     *     createUrl: string
     * }
     */
    public function index(): array
    {
        return [
            'section' => $this->navigation->section(Sections::TRIBUTES, self::ROUTE_INDEX),
            'tributes' => $this->tributes->paginateApproved(PageSizes::TRIBUTES),
            'copy' => [
                'label' => Taglines::TRIBUTES_LABEL,
                'heading' => Taglines::TRIBUTES_HEADING,
                'leaveTribute' => Taglines::LEAVE_TRIBUTE,
            ],
            'shareImage' => $this->shareImage(),
            'createUrl' => route(self::ROUTE_CREATE),
        ];
    }

    /**
     * @return array{
     *     section: NavItem,
     *     title: string,
     *     relationships: array<string, string>,
     *     messageMaxLength: int,
     *     storeUrl: string,
     *     indexUrl: string
     * }
     */
    public function create(): array
    {
        return [
            'section' => $this->navigation->section(Sections::TRIBUTES, self::ROUTE_CREATE),
            'title' => Taglines::LEAVE_TRIBUTE,
            'relationships' => $this->relationshipOptions(),
            'messageMaxLength' => Tribute::MESSAGE_MAX_LENGTH,
            'storeUrl' => route(self::ROUTE_STORE),
            'indexUrl' => route(self::ROUTE_INDEX),
        ];
    }

    /**
     * @return array{section: NavItem, message: string, indexUrl: string}
     */
    public function thanks(): array
    {
        return [
            'section' => $this->navigation->section(Sections::TRIBUTES, self::ROUTE_THANKS),
            'message' => Taglines::TRIBUTE_THANKS,
            'indexUrl' => route(self::ROUTE_INDEX),
        ];
    }

    /**
     * Absolute URL of the static Open Graph image for the Tributes page.
     */
    public function shareImage(): string
    {
        return asset(SocialCards::TRIBUTES);
    }

    /**
     * value => label, in the enum's order.
     *
     * @return array<string, string>
     */
    public function relationshipOptions(): array
    {
        $options = [];

        foreach (TributeRelationship::cases() as $relationship) {
            $options[$relationship->value] = $relationship->label();
        }

        return $options;
    }
}
