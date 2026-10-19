<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Fixed copy for the Scientific Community page: what the community is, who
 * may join, the conduct rules members agree to and the privacy note. Edited
 * here, in one place, never in the views.
 */
final class CommunityCopy
{
    public const string LABEL = 'Scientific Community';

    public const string HEADING = 'A private circle of colleagues and former students.';

    public const string INTRO = 'The Scientific Community is a private space for researchers, former students and colleagues of Professor Khidir to keep in touch, share work in progress and continue the conversations he began. Membership is by application and every application is read by an administrator.';

    public const string WHO_HEADING = 'Who may join';

    public const string WHO = 'Academics and practitioners in agriculture and the related sciences, former students of Professor Khidir, and colleagues from the institutions where he taught and served. Please tell us briefly who you are and why you would like to join.';

    public const string CONDUCT_HEADING = 'Conduct';

    /**
     * @return list<string>
     */
    public static function conductRules(): array
    {
        return [
            'Be courteous. Disagree with ideas, never with people.',
            'Keep discussion to scholarship, teaching and the life and work of Professor Khidir.',
            'Share only material you have the right to share, and credit its authors.',
            'Do not post advertising, fundraising or personal data about others.',
            'Messages may be reported to the administrators, who may remove them and suspend members who break these rules.',
        ];
    }

    public const string PRIVACY_HEADING = 'Privacy';

    public const string PRIVACY = 'Your name, email address, affiliation, academic field and reason for joining are used only to review your application and, if it is approved, to show your name and affiliation beside your messages to other members. They are kept while your account exists and are never published, sold or shared outside the site. Administrators can see your application; other members see only your name and affiliation. You can ask for your account to be removed at any time by writing to the site administrator.';

    public const string CONDUCT_AGREEMENT = 'I have read the conduct rules and agree to follow them.';

    public const string PRIVACY_AGREEMENT = 'I understand how my details will be used and kept, as described in the privacy note.';

    public const string APPLY_HEADING = 'Apply to join';

    public const string APPLY_INTRO = 'Fill in the form below. You will receive an email to confirm your address, and a second one once your application has been reviewed.';

    public const string SUBMITTED = 'Thank you. Please confirm your email address using the link we have sent you; your application will then be reviewed.';

    public const string PENDING_HEADING = 'Your application is being reviewed';

    public const string PENDING = 'Thank you for applying. An administrator will read your application and you will receive an email once a decision has been made.';

    public const string REJECTED_HEADING = 'Your application was not accepted';

    public const string REJECTED = 'Thank you for your interest in the Scientific Community. On this occasion your application has not been accepted. If you believe there has been a mistake, please write to the site administrator.';

    public const string SUSPENDED_HEADING = 'Your membership is suspended';

    public const string SUSPENDED = 'Your access to the community chat has been suspended by an administrator. If you would like to discuss this, please write to the site administrator.';

    public const string APPROVED_HEADING = 'Welcome to the community';

    public const string APPROVED = 'Your application has been approved. The chat is open to you.';

    public const string REMOVED_HEADING = 'You are no longer a member';

    public const string REMOVED = 'Your membership of the Scientific Community has ended. You are welcome to apply again.';

    public const string NOT_APPLIED_HEADING = 'Join the community';

    public const string SIGNED_IN_NOT_APPLIED = 'You are signed in but have not applied to join the community.';

    public const string CHAT_INTRO = 'Messages are visible to approved members and administrators only.';
}
