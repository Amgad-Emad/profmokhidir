<?php

declare(strict_types=1);

namespace App\Constants;

use App\Constants\Concerns\HasValues;

/**
 * Why a revision was written: an autosave of a draft, a save, a publish, or
 * the restore of an earlier version. Shown in the version history.
 */
enum RevisionKind: string
{
    use HasValues;

    case Autosave = 'autosave';
    case Save = 'save';
    case Publish = 'publish';
    case Restore = 'restore';

    public function label(): string
    {
        return match ($this) {
            self::Autosave => 'Autosaved',
            self::Save => 'Saved',
            self::Publish => 'Published',
            self::Restore => 'Restored an earlier version',
        };
    }
}
