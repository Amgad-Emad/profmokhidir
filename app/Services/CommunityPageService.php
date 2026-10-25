<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CommunityStatus;
use App\Constants\Pages;
use App\Constants\Sections;
use App\Constants\StatusTone;
use App\Models\User;
use Illuminate\Support\HtmlString;

/**
 * Assembles the public Scientific Community page: the explanation, the
 * conduct rules and privacy note, and either the signup form (visitors) or
 * the signed-in user's standing with the community.
 *
 * @phpstan-type NavItem array{key: string, label: string, title: string, url: string, route: string, placement: string, summary: string, active: bool}
 * @phpstan-type Standing array{status: CommunityStatus, heading: string, message: string, tone: StatusTone, chatUrl: ?string, canApply: bool}
 */
class CommunityPageService
{
    public const string ROUTE_INDEX = 'community';

    public const string ROUTE_APPLY = 'community.apply';

    public const string ROUTE_CHAT = 'community.chat';

    public function __construct(
        private readonly NavigationService $navigation,
        private readonly PageContentService $pages,
    ) {}

    /**
     * @return array{
     *     section: NavItem,
     *     chapter: int,
     *     copy: array{heading: string, intro: HtmlString, whoHeading: string, who: HtmlString, conductHeading: string, conduct: list<string>, privacyHeading: string, privacy: HtmlString, conductAgreement: string, privacyAgreement: string, applyHeading: string, applyIntro: string, chatButton: string, accountButton: string},
     *     standing: ?Standing,
     *     applyUrl: string,
     *     loginUrl: string
     * }
     */
    public function index(?User $user): array
    {
        return [
            'section' => $this->navigation->section(Sections::COMMUNITY, self::ROUTE_INDEX),
            'chapter' => $this->navigation->chapter(Sections::COMMUNITY),
            'copy' => [
                'heading' => $this->text('heading'),
                'intro' => $this->pages->html(Pages::COMMUNITY, 'lead'),
                'whoHeading' => $this->text('who_heading'),
                'who' => $this->pages->html(Pages::COMMUNITY, 'who'),
                'conductHeading' => $this->text('conduct_heading'),
                'conduct' => $this->conductRules(),
                'privacyHeading' => $this->text('privacy_heading'),
                'privacy' => $this->pages->html(Pages::COMMUNITY, 'privacy'),
                'conductAgreement' => $this->text('conduct_agreement'),
                'privacyAgreement' => $this->text('privacy_agreement'),
                'applyHeading' => $this->text('apply_heading'),
                'applyIntro' => $this->text('apply_intro'),
                'chatButton' => $this->text('chat_button'),
                'accountButton' => $this->text('account_button'),
            ],
            'standing' => $user === null ? null : $this->standing($user),
            'applyUrl' => route(self::ROUTE_APPLY),
            'loginUrl' => route('login'),
        ];
    }

    /**
     * What a signed-in user sees instead of the form.
     *
     * @return Standing
     */
    public function standing(User $user): array
    {
        $user->unsetRelation('roles');

        if ($user->isAdmin()) {
            return $this->state(CommunityStatus::Approved, 'approved_heading', 'chat_intro', StatusTone::Cypress, route(self::ROUTE_CHAT), false);
        }

        return match ($user->community_status) {
            CommunityStatus::Pending => $this->state(CommunityStatus::Pending, 'pending_heading', 'pending', StatusTone::Bronze, null, false),
            CommunityStatus::Rejected => $this->state(CommunityStatus::Rejected, 'rejected_heading', 'rejected', StatusTone::Ash, null, false),
            CommunityStatus::Suspended => $this->state(CommunityStatus::Suspended, 'suspended_heading', 'suspended', StatusTone::Ash, null, false),
            CommunityStatus::Approved => $user->canAccessChat()
                ? $this->state(CommunityStatus::Approved, 'approved_heading', 'approved', StatusTone::Cypress, route(self::ROUTE_CHAT), false)
                : $this->state(CommunityStatus::Suspended, 'suspended_heading', 'suspended', StatusTone::Ash, null, false),
            CommunityStatus::None => $user->reviewed_at === null
                ? $this->state(CommunityStatus::None, 'not_applied_heading', 'not_applied', StatusTone::Ash, null, ! $user->canAccessAdmin())
                : $this->state(CommunityStatus::None, 'removed_heading', 'removed', StatusTone::Ash, null, true),
        };
    }

    /**
     * One line of the community page's copy (Admin › Pages › Scientific Community).
     */
    public function text(string $key): string
    {
        return $this->pages->text(Pages::COMMUNITY, $key);
    }

    /**
     * The conduct rules, in order.
     *
     * @return list<string>
     */
    public function conductRules(): array
    {
        return array_column($this->pages->items(Pages::COMMUNITY, 'conduct'), 'text');
    }

    /**
     * @return Standing
     */
    private function state(CommunityStatus $status, string $heading, string $message, StatusTone $tone, ?string $chatUrl, bool $canApply): array
    {
        return [
            'status' => $status,
            'heading' => $this->text($heading),
            'message' => $this->text($message),
            'tone' => $tone,
            'chatUrl' => $chatUrl,
            'canApply' => $canApply,
        ];
    }
}
