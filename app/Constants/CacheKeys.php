<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Cache keys for public page queries. Services remember under these keys and
 * forget them whenever the related content is saved.
 */
final class CacheKeys
{
    public const int TTL_SECONDS = 3600;

    public const string PROFILE = 'public.profile';

    public const string CV_ENTRIES = 'public.cv_entries';

    public const string PUBLICATIONS = 'public.publications';

    public const string AWARDS = 'public.awards';

    public const string VIDEOS = 'public.videos';

    public const string ALBUMS = 'public.albums';

    public const string TEACHING_MATERIALS = 'public.teaching_materials';

    public const string COMMENTARIES = 'public.commentaries';

    public const string OTHER_ITEMS = 'public.other_items';

    public const string TRIBUTES = 'public.tributes';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::PROFILE, self::CV_ENTRIES, self::PUBLICATIONS, self::AWARDS, self::VIDEOS,
            self::ALBUMS, self::TEACHING_MATERIALS, self::COMMENTARIES, self::OTHER_ITEMS, self::TRIBUTES,
        ];
    }
}
