<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * The per-tribute share card: the brand's Instagram tribute layout, a 1080×1080
 * square on evergreen with the soft brass glow. "Remembering" in label style at
 * the head, the tribute in Cormorant Garamond Light Italic, the name in spaced
 * capitals (small caps), relationship and place as a label, then the mark and
 * the domain. Each card is drawn once by App\Services\TributeShareCardService
 * with the GD canvas in App\Support\BrandCanvas and kept on the public disk.
 */
final class TributeCards
{
    public const int WIDTH = 1080;

    public const int HEIGHT = 1080;

    /** Directory on the public disk (Disks::PUBLIC) that holds the cards. */
    public const string DIRECTORY = 'tributes/share';

    public const string EXTENSION = 'png';

    /** Label at the head of every card. */
    public const string LABEL = Taglines::TRIBUTES_LABEL;

    /** Shown in label style at the foot of the card. */
    public const string DOMAIN = SocialCards::DOMAIN;

    /** Ring diameter of the mark on the card, in pixels. */
    public const int MARK_SIZE_PX = 88;

    /** Safe margin on every side, in pixels. */
    public const int MARGIN_PX = 96;

    /** The tribute text is tried at each size in turn until it fits QUOTE_MAX_LINES. */
    public const array QUOTE_SIZES_PX = [52, 46, 40, 36];

    public const int QUOTE_MAX_LINES = 9;

    public const float QUOTE_LEADING = 1.3;

    /** Longer tributes are cut at a word boundary and closed with an ellipsis. */
    public const int EXCERPT_MAX_CHARS = 420;

    public const int NAME_SIZE_PX = 30;

    public const float NAME_TRACKING_EM = 0.12;

    public const int LABEL_SIZE_PX = 16;

    public const int RULE_WIDTH_PX = 48;

    /** The route that serves a tribute's card (App\Http\Controllers\TributeController::card). */
    public const string ROUTE = 'tributes.card';

    /** Download name: tribute-{id}.png. */
    public const string FILENAME_PREFIX = 'tribute-';

    public static function path(int $id): string
    {
        return self::DIRECTORY.'/'.self::FILENAME_PREFIX.$id.'.'.self::EXTENSION;
    }

    public static function filename(int $id): string
    {
        return self::FILENAME_PREFIX.$id.'.'.self::EXTENSION;
    }
}
