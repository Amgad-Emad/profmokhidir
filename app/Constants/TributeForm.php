<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * The guided tribute flow: two steps (write, then preview) before the tribute
 * is sent, each with its calm intro line and the hints beside the fields.
 */
final class TributeForm
{
    public const string STEP_WRITE = 'write';

    public const string STEP_PREVIEW = 'preview';

    public const string INTRO = 'Share a memory, a lesson or a moment. Every tribute is read before it is published.';

    public const string PREVIEW_INTRO = 'This is how your tribute will appear. Change anything you like, or send it as it is.';

    public const string CONSENT = 'I agree that this tribute may be published on this site with my name, relationship and location.';

    public const string FIELD_NAME = 'name';

    public const string FIELD_RELATIONSHIP = 'relationship';

    public const string FIELD_LOCATION = 'location';

    public const string FIELD_MESSAGE = 'message';

    public const string FIELD_EMAIL = 'email';

    /**
     * Step key => label, in order.
     *
     * @return array<string, string>
     */
    public static function steps(): array
    {
        return [
            self::STEP_WRITE => 'Write',
            self::STEP_PREVIEW => 'Preview',
        ];
    }

    /**
     * 1-based position of a step.
     */
    public static function position(string $step): int
    {
        $position = array_search($step, array_keys(self::steps()), true);

        return $position === false ? 1 : $position + 1;
    }

    /**
     * Field => label and hint.
     *
     * @return array<string, array{label: string, hint: string}>
     */
    public static function fields(): array
    {
        return [
            self::FIELD_NAME => ['label' => 'Your name', 'hint' => 'As you would like it to appear beside your tribute.'],
            self::FIELD_RELATIONSHIP => ['label' => 'How you knew him', 'hint' => 'Student, colleague, family or friend.'],
            self::FIELD_LOCATION => ['label' => 'Where you are writing from', 'hint' => 'Optional. Town or city and country, for example Khartoum, Sudan.'],
            self::FIELD_MESSAGE => ['label' => 'Your tribute', 'hint' => 'Share a memory, a lesson or a moment. A few lines are enough.'],
            self::FIELD_EMAIL => ['label' => 'Email', 'hint' => 'Optional, and never shown publicly. Only used if we need to reach you about your tribute.'],
        ];
    }

    public static function label(string $field): string
    {
        return self::fields()[$field]['label'];
    }

    public static function hint(string $field): string
    {
        return self::fields()[$field]['hint'];
    }
}
