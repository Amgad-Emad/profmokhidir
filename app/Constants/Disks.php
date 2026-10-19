<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Filesystem disks (config/filesystems.php) used for media.
 */
final class Disks
{
    /** Public content: portraits, photos, thumbnails, public files. */
    public const string PUBLIC = 'public';

    /** Members-only teaching files, served through a controller after an access check. */
    public const string PRIVATE = 'private';
}
