<?php

declare(strict_types=1);

namespace App\Constants;

use App\Models\Album;
use App\Models\Award;
use App\Models\Commentary;
use App\Models\CvEntry;
use App\Models\HomeQuote;
use App\Models\OtherItem;
use App\Models\Profile;
use App\Models\Publication;
use App\Models\TeachingMaterial;
use App\Models\Tribute;
use App\Models\Video;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Classes the cache stores may unserialise (config/cache.php "serializable_classes").
 * Laravel refuses every object by default; the public page services cache
 * published content as Eloquent models with their media loaded, so those
 * models and the framework classes inside them are listed here explicitly.
 * Anything not listed comes back as __PHP_Incomplete_Class and breaks the page.
 */
final class CacheSerializable
{
    /**
     * Content models the services cache for the public site.
     *
     * @return list<class-string>
     */
    public static function models(): array
    {
        return [
            Profile::class, CvEntry::class, Publication::class, Award::class, Video::class,
            Album::class, TeachingMaterial::class, Commentary::class, OtherItem::class, Tribute::class, HomeQuote::class,
        ];
    }

    /**
     * Framework and package classes that appear inside a serialised model.
     *
     * @return list<class-string>
     */
    public static function framework(): array
    {
        return [
            EloquentCollection::class, Collection::class, Carbon::class, CarbonImmutable::class,
            Media::class, MediaCollection::class,
        ];
    }

    /**
     * Backed enums used as attribute casts on the cached models.
     *
     * @return list<class-string>
     */
    public static function enums(): array
    {
        return [
            ContentStatus::class, CvSection::class, PublicationType::class, MediaTreatment::class,
            TributeRelationship::class, TributeStatus::class, TeachingAccess::class,
            TeachingMaterialType::class, VideoProvider::class,
        ];
    }

    /**
     * @return list<class-string>
     */
    public static function classes(): array
    {
        return [...self::models(), ...self::framework(), ...self::enums()];
    }
}
