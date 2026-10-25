<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Section cards (<x-section-card>): the "explore" cards that lead from the home
 * page and About into a section. Each card is one link: the section's chapter
 * numeral in the label style, its page heading in Cormorant, its intro in one or
 * two lines and "Explore →" on the bottom edge, with an optional duotone strip
 * from the section page's card image (Admin › Pages). The grid (.section-cards)
 * fills the content width: auto-fit columns of at least MIN_COLUMN_PX, GAP_PX
 * apart, so four or three cards a row on desktop, two on tablet, one on phones.
 * The sizes mirror resources/css/section-cards.css.
 */
final class SectionCards
{
    /** The narrowest a column may get before the grid drops a column. */
    public const int MIN_COLUMN_PX = 280;

    public const int GAP_PX = 24;

    public const int PADDING_PX = 28;

    public const int PADDING_MOBILE_PX = 24;

    /** The heading size (Cormorant Garamond 300). */
    public const int TITLE_PX = 28;

    /** The description size (Hanken Grotesk 400). */
    public const int SUMMARY_PX = 17;

    /** Hover: the card rises this far and the arrow moves this far right (none under reduced motion). */
    public const int HOVER_RISE_PX = 2;

    public const int ARROW_SHIFT_PX = 4;

    /** The call to action on every card, followed by the arrow. */
    public const string ACTION = 'Explore';

    public const string ARROW = '→';

    /** The image block of a section page (Admin › Pages) that gives its card a duotone strip. */
    public const string IMAGE_BLOCK = 'card_image';

    /** The strip's shape. */
    public const string IMAGE_ASPECT = '3 / 1';

    public const string IMAGE_SIZES = '(min-width: 1024px) 24rem, (min-width: 640px) 50vw, 100vw';
}
