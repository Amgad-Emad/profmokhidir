<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Motion specification. The CSS in resources/css/app.css and resources/js/motion.js
 * implement these values; they are kept here for the style guide and for tests.
 */
final class Motion
{
    public const string EASING = 'cubic-bezier(0.16, 1, 0.3, 1)';

    public const int RISE_DISTANCE_PX = 40;

    public const int RISE_DURATION_MS = 1200;

    public const int STAGGER_MS = 120;

    public const int RULE_DURATION_MS = 1600;

    public const float HERO_SCALE_FROM = 1.0;

    public const float HERO_SCALE_TO = 0.7;

    /** HTML attributes that opt an element into each behaviour. */
    public const string ATTR_RISE = 'data-rise';

    public const string ATTR_RISE_GROUP = 'data-rise-group';

    public const string ATTR_RULE = 'data-rule';

    public const string ATTR_SCROLL_SCALE = 'data-scroll-scale';

    /**
     * @return array<string, array{attribute: string, summary: string}>
     */
    public static function behaviours(): array
    {
        return [
            'rise' => [
                'attribute' => self::ATTR_RISE,
                'summary' => sprintf('Fades in and rises %dpx over %dms with %s when it enters the viewport.', self::RISE_DISTANCE_PX, self::RISE_DURATION_MS, self::EASING),
            ],
            'rise-group' => [
                'attribute' => self::ATTR_RISE_GROUP,
                'summary' => sprintf('Children rise one after another, staggered by %dms.', self::STAGGER_MS),
            ],
            'rule' => [
                'attribute' => self::ATTR_RULE,
                'summary' => sprintf('Brass rule draws from left to right over %dms, ease-in-out, when in view.', self::RULE_DURATION_MS),
            ],
            'scroll-scale' => [
                'attribute' => self::ATTR_SCROLL_SCALE,
                'summary' => sprintf('Scales from %.1f to %.1f and fades slightly as the hero scrolls out.', self::HERO_SCALE_FROM, self::HERO_SCALE_TO),
            ],
        ];
    }
}
