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

    /** The header condenses once the page has scrolled this far. */
    public const int HEADER_CONDENSE_AFTER_PX = 40;

    public const int HEADER_CONDENSE_DURATION_MS = 600;

    /** Cross-fade between pages (View Transitions API); the header stays in place. */
    public const int PAGE_TRANSITION_MS = 400;

    public const string PAGE_TRANSITION_HEADER_NAME = 'site-header';

    /** Hairline underlines draw left to right; outline buttons fill at the same pace. */
    public const int LINK_DRAW_MS = 600;

    /** HTML attributes that opt an element into each behaviour. */
    public const string ATTR_RISE = 'data-rise';

    public const string ATTR_RISE_GROUP = 'data-rise-group';

    public const string ATTR_RULE = 'data-rule';

    public const string ATTR_SCROLL_SCALE = 'data-scroll-scale';

    public const string ATTR_SCROLL_LINE = 'data-scroll-line';

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
            'scroll-line' => [
                'attribute' => self::ATTR_SCROLL_LINE,
                'summary' => 'A brass hairline draws downward with the scroll as its chapter passes the reading line; fully drawn without scripts or with reduced motion.',
            ],
        ];
    }
}
