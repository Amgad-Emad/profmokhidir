<?php

declare(strict_types=1);

namespace App\Constants\PageDefinitions;

use App\Constants\CommunityCopy;
use App\Constants\Masthead;
use App\Constants\Sections;
use App\Support\PageBlockDefinition as Block;

/**
 * The editable blocks of the Scientific Community page: what it is, who may
 * join, the conduct rules, the privacy note, the application and every message
 * an applicant or member sees about their standing. Defaults are
 * App\Constants\CommunityCopy.
 */
final class CommunityBlocks
{
    public const string SECTION_OPENING = 'Opening';

    public const string SECTION_WHO = 'Who may join';

    public const string SECTION_CONDUCT = 'Conduct';

    public const string SECTION_PRIVACY = 'Privacy note';

    public const string SECTION_APPLY = 'Applying';

    public const string SECTION_STANDING = 'Messages about an application';

    public const string SECTION_SEO = 'Search & sharing';

    public const int CONDUCT_RULES_MAX = 10;

    /**
     * @return list<Block>
     */
    public static function definitions(): array
    {
        $section = Sections::definitions()[Sections::COMMUNITY];
        $standing = self::SECTION_STANDING;

        return [
            Block::shortText('title', 'Page name', self::SECTION_OPENING, $section['title'], 60, hint: 'Its name in breadcrumbs, search results and the chat.'),
            Block::shortText('intro', 'Summary', self::SECTION_OPENING, $section['summary'], 240, hint: 'Used in menus, cards and as the search description.'),
            Block::shortText('heading', 'Page heading', self::SECTION_OPENING, CommunityCopy::HEADING, 120),
            Block::longText('lead', 'Introduction', self::SECTION_OPENING, CommunityCopy::INTRO, 700),

            Block::shortText('who_heading', 'Label', self::SECTION_WHO, CommunityCopy::WHO_HEADING, 40),
            Block::longText('who', 'Who may join', self::SECTION_WHO, CommunityCopy::WHO, 600),

            Block::shortText('conduct_heading', 'Label', self::SECTION_CONDUCT, CommunityCopy::CONDUCT_HEADING, 40),
            Block::list('conduct', 'Conduct rules', self::SECTION_CONDUCT,
                array_map(fn (string $rule): array => ['text' => $rule], CommunityCopy::conductRules()),
                ['text' => Block::field('Rule', 200)], 'Rule', maxItems: self::CONDUCT_RULES_MAX,
                hint: 'Numbered in this order on the page and in the chat. Applicants agree to them when they apply.'),
            Block::shortText('conduct_agreement', 'Checkbox: agree to the rules', self::SECTION_CONDUCT, CommunityCopy::CONDUCT_AGREEMENT, 200),

            Block::shortText('privacy_heading', 'Label', self::SECTION_PRIVACY, CommunityCopy::PRIVACY_HEADING, 40),
            Block::longText('privacy', 'Privacy note', self::SECTION_PRIVACY, CommunityCopy::PRIVACY, 1000),
            Block::shortText('privacy_agreement', 'Checkbox: understand the privacy note', self::SECTION_PRIVACY, CommunityCopy::PRIVACY_AGREEMENT, 200),

            Block::shortText('apply_heading', 'Label', self::SECTION_APPLY, CommunityCopy::APPLY_HEADING, 40),
            Block::shortText('apply_intro', 'Introduction to the form', self::SECTION_APPLY, CommunityCopy::APPLY_INTRO, 300),
            Block::shortText('submitted', 'Message after applying', self::SECTION_APPLY, CommunityCopy::SUBMITTED, 300),

            Block::shortText('pending_heading', 'Under review: heading', $standing, CommunityCopy::PENDING_HEADING, 80),
            Block::shortText('pending', 'Under review: message', $standing, CommunityCopy::PENDING, 300),
            Block::shortText('approved_heading', 'Approved: heading', $standing, CommunityCopy::APPROVED_HEADING, 80),
            Block::shortText('approved', 'Approved: message', $standing, CommunityCopy::APPROVED, 300),
            Block::shortText('chat_intro', 'Approved members: line about the chat', $standing, CommunityCopy::CHAT_INTRO, 200, hint: 'Also shown in the chat.'),
            Block::shortText('chat_button', 'Button: Open the chat', $standing, Masthead::OPEN_CHAT, 30),
            Block::shortText('account_button', 'Button: Your account', $standing, Masthead::ACCOUNT, 30),
            Block::shortText('rejected_heading', 'Not accepted: heading', $standing, CommunityCopy::REJECTED_HEADING, 80),
            Block::shortText('rejected', 'Not accepted: message', $standing, CommunityCopy::REJECTED, 300),
            Block::shortText('suspended_heading', 'Suspended: heading', $standing, CommunityCopy::SUSPENDED_HEADING, 80),
            Block::shortText('suspended', 'Suspended: message', $standing, CommunityCopy::SUSPENDED, 300, hint: 'Also shown in the chat.'),
            Block::shortText('removed_heading', 'Membership ended: heading', $standing, CommunityCopy::REMOVED_HEADING, 80),
            Block::shortText('removed', 'Membership ended: message', $standing, CommunityCopy::REMOVED, 300),
            Block::shortText('not_applied_heading', 'Signed in, not applied: heading', $standing, CommunityCopy::NOT_APPLIED_HEADING, 80),
            Block::shortText('not_applied', 'Signed in, not applied: message', $standing, CommunityCopy::SIGNED_IN_NOT_APPLIED, 300),

            Block::seo(self::SECTION_SEO, hint: SectionBlocks::SEO_HINT),
        ];
    }
}
