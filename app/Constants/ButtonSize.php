<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Button sizes. Medium is the 44px pill; Small is the 36px pill for rows and the
 * masthead (its hit area is still padded to 44px where it stands alone); Round is
 * a 44px circle for an icon-only control in any variant (the chat send button is
 * a primary round button). Toolbar is the 40px pill of the admin list toolbar, its
 * wording set as a 12px label.
 */
enum ButtonSize: string
{
    case Medium = 'md';
    case Small = 'sm';
    case Round = 'round';
    case Toolbar = 'toolbar';

    public function cssClass(): ?string
    {
        return match ($this) {
            self::Medium => null,
            self::Small => 'btn--small',
            self::Round => 'btn--round',
            self::Toolbar => 'btn--toolbar',
        };
    }
}
