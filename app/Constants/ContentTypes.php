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
use App\Models\Video;
use App\Services\AbstractContentService;
use App\Services\AlbumService;
use App\Services\AwardService;
use App\Services\CommentaryService;
use App\Services\CvEntryService;
use App\Services\HomeQuoteService;
use App\Services\OtherItemService;
use App\Services\ProfileService;
use App\Services\PublicationService;
use App\Services\TeachingMaterialService;
use App\Services\VideoService;
use Illuminate\Database\Eloquent\Model;
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

    public const string HOME_QUOTE = 'home_quote';

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
            self::HOME_QUOTE => self::type(HomeQuote::class, 'Home quote', 'Home quotes', AdminNavigation::HOME_QUOTES, 'home'),
        ];
    }

    /**
     * What goes in each type's list, in plain words, for its empty state.
     */
    public static function emptyText(string $key): string
    {
        return match ($key) {
            self::CV_ENTRY => 'CV entries are the lines of his CV: degrees, posts, memberships. Each one appears on the CV page under its section.',
            self::PUBLICATION => 'Publications are his papers, books and chapters. Each can carry a PDF of the full text, and readers can filter them by type and year.',
            self::AWARD => 'Awards are the honours and prizes he received, with the organisation and the year.',
            self::VIDEO => 'Videos are recordings of lectures, interviews and ceremonies, linked from YouTube or Vimeo or uploaded.',
            self::ALBUM => 'Albums group photographs for the Pictures page. Each photo has a caption with place and year.',
            self::TEACHING_MATERIAL => 'Teaching materials are lecture notes, syllabi and problem sets, grouped by course on the Teaching page.',
            self::COMMENTARY => 'Commentaries are essays and notes for the Commentary Corner, each on its own page.',
            self::OTHER_ITEM => 'Other information holds anything that fits no other section: interviews, dedications, events.',
            self::HOME_QUOTE => 'Home quotes are short lines in his words, shown between the chapters of the home page.',
            default => 'Nothing has been added here yet.',
        };
    }

    /**
     * Type key => the content service that manages it.
     *
     * @return array<string, class-string<AbstractContentService<Model>>>
     */
    public static function services(): array
    {
        return [
            self::PROFILE => ProfileService::class,
            self::CV_ENTRY => CvEntryService::class,
            self::PUBLICATION => PublicationService::class,
            self::AWARD => AwardService::class,
            self::VIDEO => VideoService::class,
            self::ALBUM => AlbumService::class,
            self::TEACHING_MATERIAL => TeachingMaterialService::class,
            self::COMMENTARY => CommentaryService::class,
            self::OTHER_ITEM => OtherItemService::class,
            self::HOME_QUOTE => HomeQuoteService::class,
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
