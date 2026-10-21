<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\PageSizes;
use App\Constants\Sections;
use App\Constants\SocialCards;
use App\Constants\Taglines;
use App\Constants\TributeCards;
use App\Constants\TributeForm;
use App\Constants\TributeRelationship;
use App\Http\Requests\TributeIndexRequest;
use App\Models\Tribute;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Assembles the public Tributes pages: the book of remembrance (the featured
 * tribute as its frontispiece, then the pages of approved tributes, filtered by
 * relationship), the guided submission flow and the calm confirmation.
 *
 * @phpstan-type NavItem array{key: string, label: string, title: string, url: string, route: string, placement: string, summary: string, active: bool}
 * @phpstan-type FilterOption array{value: string, label: string, url: string, active: bool}
 */
class TributesPageService
{
    public const string ROUTE_INDEX = 'tributes';

    public const string ROUTE_CREATE = 'tributes.create';

    public const string ROUTE_PREVIEW = 'tributes.preview';

    public const string ROUTE_EDIT = 'tributes.edit';

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
     *     featured: Tribute|null,
     *     filters: list<FilterOption>,
     *     activeRelationship: TributeRelationship|null,
     *     clearUrl: string,
     *     copy: array{label: string, heading: string, leaveTribute: string, featuredLabel: string, bookLabel: string},
     *     shareImage: string,
     *     createUrl: string
     * }
     */
    public function index(?TributeRelationship $relationship = null): array
    {
        $tributes = $this->tributes->paginateApproved(PageSizes::TRIBUTES, $relationship);
        $featured = $this->tributes->featured();

        // The frontispiece opens the book: on its first page only, and only when it belongs to the chosen relationship.
        if ($featured !== null && ($tributes->currentPage() !== 1 || ($relationship !== null && $featured->relationship !== $relationship))) {
            $featured = null;
        }

        return [
            'section' => $this->navigation->section(Sections::TRIBUTES, self::ROUTE_INDEX),
            'tributes' => $tributes,
            'featured' => $featured,
            'filters' => $this->filterOptions($relationship),
            'activeRelationship' => $relationship,
            'clearUrl' => $this->url(),
            'copy' => [
                'label' => Taglines::TRIBUTES_LABEL,
                'heading' => Taglines::TRIBUTES_HEADING,
                'leaveTribute' => Taglines::LEAVE_TRIBUTE,
                'featuredLabel' => Taglines::TRIBUTES_FEATURED_LABEL,
                'bookLabel' => Taglines::TRIBUTES_BOOK_LABEL,
            ],
            'shareImage' => $this->shareImage(),
            'createUrl' => route(self::ROUTE_CREATE),
        ];
    }

    /**
     * @return array{
     *     section: NavItem,
     *     title: string,
     *     step: string,
     *     steps: array<string, string>,
     *     intro: string,
     *     fields: array<string, array{label: string, hint: string}>,
     *     relationships: array<string, string>,
     *     messageMaxLength: int,
     *     previewUrl: string,
     *     indexUrl: string
     * }
     */
    public function create(): array
    {
        return [
            'section' => $this->navigation->section(Sections::TRIBUTES, self::ROUTE_CREATE),
            'title' => Taglines::LEAVE_TRIBUTE,
            'step' => TributeForm::STEP_WRITE,
            'steps' => TributeForm::steps(),
            'intro' => TributeForm::INTRO,
            'fields' => TributeForm::fields(),
            'relationships' => $this->relationshipOptions(),
            'messageMaxLength' => Tribute::MESSAGE_MAX_LENGTH,
            'previewUrl' => route(self::ROUTE_PREVIEW),
            'indexUrl' => route(self::ROUTE_INDEX),
        ];
    }

    /**
     * The preview step: the tribute exactly as it will appear, built from the
     * (validated) fields, with the consent and the hidden fields that send it.
     *
     * @param  array{name: string, relationship: string, location: ?string, message: string, email: ?string}  $data
     * @return array{
     *     section: NavItem,
     *     title: string,
     *     step: string,
     *     steps: array<string, string>,
     *     intro: string,
     *     consent: string,
     *     tribute: Tribute,
     *     data: array{name: string, relationship: string, location: ?string, message: string, email: ?string},
     *     storeUrl: string,
     *     editUrl: string
     * }
     */
    public function preview(array $data): array
    {
        $tribute = new Tribute([
            'name' => trim($data['name']),
            'relationship' => TributeRelationship::from($data['relationship']),
            'location' => filled($data['location'] ?? null) ? trim((string) $data['location']) : null,
            'message' => trim($data['message']),
        ]);

        return [
            'section' => $this->navigation->section(Sections::TRIBUTES, self::ROUTE_PREVIEW),
            'title' => Taglines::LEAVE_TRIBUTE,
            'step' => TributeForm::STEP_PREVIEW,
            'steps' => TributeForm::steps(),
            'intro' => TributeForm::PREVIEW_INTRO,
            'consent' => TributeForm::CONSENT,
            'tribute' => $tribute,
            'data' => $data,
            'storeUrl' => route(self::ROUTE_STORE),
            'editUrl' => route(self::ROUTE_EDIT),
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
     * URL of the book, optionally filtered to one relationship.
     */
    public function url(?TributeRelationship $relationship = null): string
    {
        return route(self::ROUTE_INDEX, array_filter([TributeIndexRequest::RELATIONSHIP => $relationship?->value], fn ($value): bool => $value !== null));
    }

    /**
     * URL of a tribute's share card.
     */
    public function cardUrl(Tribute $tribute): string
    {
        return route(TributeCards::ROUTE, $tribute);
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

    /**
     * The quiet small-caps filter links: the four relationships of the brief.
     *
     * @return list<FilterOption>
     */
    private function filterOptions(?TributeRelationship $active): array
    {
        return array_map(fn (TributeRelationship $relationship): array => [
            'value' => $relationship->value,
            'label' => $relationship->plural(),
            'url' => $this->url($relationship),
            'active' => $relationship === $active,
        ], TributeRelationship::filterable());
    }
}
