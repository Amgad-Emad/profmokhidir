<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CommunityCopy;
use App\Constants\CommunityStatus;
use App\Constants\Sections;
use App\Constants\StatusTone;
use App\Models\User;

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

    public function __construct(private readonly NavigationService $navigation) {}

    /**
     * @return array{
     *     section: NavItem,
     *     chapter: int,
     *     copy: array{heading: string, intro: string, whoHeading: string, who: string, conductHeading: string, conduct: list<string>, privacyHeading: string, privacy: string, conductAgreement: string, privacyAgreement: string, applyHeading: string, applyIntro: string},
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
                'heading' => CommunityCopy::HEADING,
                'intro' => CommunityCopy::INTRO,
                'whoHeading' => CommunityCopy::WHO_HEADING,
                'who' => CommunityCopy::WHO,
                'conductHeading' => CommunityCopy::CONDUCT_HEADING,
                'conduct' => CommunityCopy::conductRules(),
                'privacyHeading' => CommunityCopy::PRIVACY_HEADING,
                'privacy' => CommunityCopy::PRIVACY,
                'conductAgreement' => CommunityCopy::CONDUCT_AGREEMENT,
                'privacyAgreement' => CommunityCopy::PRIVACY_AGREEMENT,
                'applyHeading' => CommunityCopy::APPLY_HEADING,
                'applyIntro' => CommunityCopy::APPLY_INTRO,
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
            return $this->state(CommunityStatus::Approved, CommunityCopy::APPROVED_HEADING, CommunityCopy::CHAT_INTRO, StatusTone::Cypress, route(self::ROUTE_CHAT), false);
        }

        return match ($user->community_status) {
            CommunityStatus::Pending => $this->state(CommunityStatus::Pending, CommunityCopy::PENDING_HEADING, CommunityCopy::PENDING, StatusTone::Bronze, null, false),
            CommunityStatus::Rejected => $this->state(CommunityStatus::Rejected, CommunityCopy::REJECTED_HEADING, CommunityCopy::REJECTED, StatusTone::Ash, null, false),
            CommunityStatus::Suspended => $this->state(CommunityStatus::Suspended, CommunityCopy::SUSPENDED_HEADING, CommunityCopy::SUSPENDED, StatusTone::Ash, null, false),
            CommunityStatus::Approved => $user->canAccessChat()
                ? $this->state(CommunityStatus::Approved, CommunityCopy::APPROVED_HEADING, CommunityCopy::APPROVED, StatusTone::Cypress, route(self::ROUTE_CHAT), false)
                : $this->state(CommunityStatus::Suspended, CommunityCopy::SUSPENDED_HEADING, CommunityCopy::SUSPENDED, StatusTone::Ash, null, false),
            CommunityStatus::None => $user->reviewed_at === null
                ? $this->state(CommunityStatus::None, CommunityCopy::NOT_APPLIED_HEADING, CommunityCopy::SIGNED_IN_NOT_APPLIED, StatusTone::Ash, null, ! $user->canAccessAdmin())
                : $this->state(CommunityStatus::None, CommunityCopy::REMOVED_HEADING, CommunityCopy::REMOVED, StatusTone::Ash, null, true),
        };
    }

    /**
     * @return Standing
     */
    private function state(CommunityStatus $status, string $heading, string $message, StatusTone $tone, ?string $chatUrl, bool $canApply): array
    {
        return [
            'status' => $status,
            'heading' => $heading,
            'message' => $message,
            'tone' => $tone,
            'chatUrl' => $chatUrl,
            'canApply' => $canApply,
        ];
    }
}
