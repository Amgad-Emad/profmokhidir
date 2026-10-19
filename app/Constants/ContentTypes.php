<?php

declare(strict_types=1);

namespace App\Constants;

use App\Models\Album;
use App\Models\Award;
use App\Models\Commentary;
use App\Models\CvEntry;
use App\Models\OtherItem;
use App\Models\Profile;
use App\Models\Publication;
use App\Models\TeachingMaterial;
use App\Models\Video;
use InvalidArgumentException;

/**
 * Registry of the editable content types: which model each admin screen
 * manages, how it is labelled, where it lives in the dashboard and where its
 * public page is. The Livewire screens, the media library ("used by") and
 * the "View on site" links are all derived from here.
 */
final class ContentTypes
{
    public const string PROFILE = 'profile';

    public const string CV_ENTRY = 'cv_entry';

    public const string PUBLICATION = 'publication';

    public const string AWARD = 'award';

    public const string VIDEO = 'video';

    public const string ALBUM = 'album';

    public const string TEACHING_MATERIAL = 'teaching_material';

    public const string COMMENTARY = 'commentary';

    public const string OTHER_ITEM = 'other_item';

    /**
     * Type key => model class, labels, the admin navigation item whose route
     * prefix the screens use, and the public route (with the attribute used as
     * its parameter, or null for list pages without detail routes).
     *
     * @return array<string, array{model: class-string, singular: string, plural: string, nav: string, public_route: string, public_param: ?string}>
     */
    public static function all(): array
    {
        return [
            self::PROFILE => self::type(Profile::class, 'Profile', 'Profile', AdminNavigation::PROFILE, 'about'),
            self::CV_ENTRY => self::type(CvEntry::class, 'CV entry', 'CV entries', AdminNavigation::CV, 'cv'),
            self::PUBLICATION => self::type(Publication::class, 'Publication', 'Publications', AdminNavigation::PUBLICATIONS, 'publications'),
            self::AWARD => self::type(Award::class, 'Award', 'Awards', AdminNavigation::AWARDS, 'awards'),
            self::VIDEO => self::type(Video::class, 'Video', 'Videos', AdminNavigation::VIDEOS, 'videos.show', 'slug'),
            self::ALBUM => self::type(Album::class, 'Album', 'Albums', AdminNavigation::ALBUMS, 'pictures.show', 'slug'),
            self::TEACHING_MATERIAL => self::type(TeachingMaterial::class, 'Teaching material', 'Teaching materials', AdminNavigation::TEACHING, 'teaching'),
            self::COMMENTARY => self::type(Commentary::class, 'Commentary', 'Commentaries', AdminNavigation::COMMENTARY, 'commentary.show', 'slug'),
            self::OTHER_ITEM => self::type(OtherItem::class, 'Other item', 'Other information', AdminNavigation::OTHER, 'other.show', 'slug'),
        ];
    }

    /**
     * @return array{model: class-string, singular: string, plural: string, nav: string, public_route: string, public_param: ?string}
     */
    public static function get(string $key): array
    {
        $types = self::all();

        if (! array_key_exists($key, $types)) {
            throw new InvalidArgumentException(sprintf('Unknown content type "%s".', $key));
        }

        return $types[$key];
    }

    /**
     * The type key of a model class (or morph alias), or null for models that are not content.
     */
    public static function keyForModel(string $modelClass): ?string
    {
        foreach (self::all() as $key => $type) {
            if ($type['model'] === $modelClass) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Route name of the admin index of a type (e.g. admin.content.publications).
     */
    public static function indexRoute(string $key): string
    {
        return AdminNavigation::routeFor(self::get($key)['nav']);
    }

    /**
     * Route name of the admin edit screen of a type. The profile has a single
     * form, so its "edit" route is its index route.
     */
    public static function editRoute(string $key): string
    {
        return $key === self::PROFILE ? self::indexRoute($key) : self::indexRoute($key).'.edit';
    }

    public static function createRoute(string $key): string
    {
        return self::indexRoute($key).'.create';
    }

    /**
     * Route parameter name used by the edit route (the model's camel-cased short name).
     */
    public static function routeParameter(string $key): string
    {
        return lcfirst(class_basename(self::get($key)['model']));
    }

    public static function isValid(string $key): bool
    {
        return array_key_exists($key, self::all());
    }

    /**
     * @param  class-string  $model
     * @return array{model: class-string, singular: string, plural: string, nav: string, public_route: string, public_param: ?string}
     */
    private static function type(string $model, string $singular, string $plural, string $nav, string $publicRoute, ?string $publicParam = null): array
    {
        return [
            'model' => $model,
            'singular' => $singular,
            'plural' => $plural,
            'nav' => $nav,
            'public_route' => $publicRoute,
            'public_param' => $publicParam,
        ];
    }
}
