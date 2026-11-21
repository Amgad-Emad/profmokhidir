<?php

namespace Database\Seeders;

use App\Constants\ContentStatus;
use App\Constants\CvSection;
use App\Constants\Locales;
use App\Constants\MediaProperties;
use App\Constants\TeachingAccess;
use App\Models\Album;
use App\Models\Award;
use App\Models\CvEntry;
use App\Models\HomeQuote;
use App\Models\Publication;
use App\Models\TeachingMaterial;
use App\Models\Video;
use App\Services\AbstractContentService;
use App\Services\AlbumService;
use App\Services\AwardService;
use App\Services\CvEntryService;
use App\Services\HomeQuoteService;
use App\Services\ProfileService;
use App\Services\PublicationService;
use App\Services\TeachingMaterialService;
use App\Services\TermTranslationService;
use App\Services\VideoService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Imports the stakeholders' archive (database/seeders/archive): the portrait,
 * four photo albums, the awards, the CV entries the certificates establish, the
 * papers, the teaching materials and the videos, each in English and Arabic.
 *
 * Run on its own, after the placeholder dataset or on a live site:
 *   php artisan db:seed --class=StakeholderArchiveSeeder
 *
 * In each section it fills, records still marked "[Placeholder]" are moved to
 * the trash (soft deleted, restorable from the admin). Running it twice does
 * nothing the second time.
 */
class StakeholderArchiveSeeder extends Seeder
{
    /** Where the curated files and the manifest live. */
    public const string ARCHIVE_PATH = __DIR__.'/archive';

    private const string PLACEHOLDER_MARK = '[Placeholder]';

    private string $staging;

    public function __construct(
        private readonly ProfileService $profiles,
        private readonly AlbumService $albums,
        private readonly AwardService $awards,
        private readonly CvEntryService $cvEntries,
        private readonly PublicationService $publications,
        private readonly TeachingMaterialService $materials,
        private readonly VideoService $videos,
        private readonly TermTranslationService $terms,
        private readonly HomeQuoteService $quotes,
    ) {}

    public function run(): void
    {
        $manifest = self::manifest();
        $firstAlbum = $manifest['albums'][0]['title']['en'];

        if (Album::query()->get()->contains(fn (Album $album): bool => $album->getTranslation('title', Locales::DEFAULT) === $firstAlbum)) {
            $this->command?->info('The stakeholders\' archive is already imported.');

            return;
        }

        $this->staging = storage_path('app/archive-import');
        File::ensureDirectoryExists($this->staging);

        try {
            $this->retirePlaceholders();
            $this->importProfile($manifest['profile'], $manifest['portrait']);
            $this->importAlbums($manifest['albums']);
            $this->importAwards($manifest['awards']);
            $this->importCv($manifest['cv']);
            $this->importQuotes($manifest['quotes']);
            $this->importPublications($manifest['publications']);
            $this->importTeaching($manifest['teaching']);
            $this->terms->save($manifest['terms']);
            $this->importVideos($manifest['videos'], $manifest['albums']);
        } finally {
            File::deleteDirectory($this->staging);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function manifest(): array
    {
        return require self::ARCHIVE_PATH.'/manifest.php';
    }

    private function retirePlaceholders(): void
    {
        $sections = [
            [$this->awards, Award::class],
            [$this->albums, Album::class],
            [$this->videos, Video::class],
            [$this->publications, Publication::class],
            [$this->materials, TeachingMaterial::class],
            [$this->cvEntries, CvEntry::class],
            [$this->quotes, HomeQuote::class],
        ];

        foreach ($sections as [$service, $model]) {
            /** @var AbstractContentService<Model> $service */
            foreach ($model::query()->get() as $record) {
                // The primary column (title or name) carries the mark on every placeholder record.
                if (str_contains((string) $record->getTranslation($record->translatable[0], Locales::DEFAULT), self::PLACEHOLDER_MARK)) {
                    $service->delete($record);
                }
            }
        }
    }

    /**
     * @param  array<string, array<string, string>>  $details  name, title and affiliation in each language
     * @param  array{file: string, properties: array<string, mixed>}  $portrait
     */
    private function importProfile(array $details, array $portrait): void
    {
        if ($this->profiles->current() === null) {
            $this->command?->warn('No profile yet: the name, title and portrait were not set.');

            return;
        }

        $profile = $this->profiles->save($details);

        $properties = $portrait['properties'];
        unset($properties[MediaProperties::IS_COVER]);

        $this->profiles->setPortrait($profile, $this->stage($portrait['file']), $properties);
    }

    /**
     * @param  list<array<string, mixed>>  $albums
     */
    private function importAlbums(array $albums): void
    {
        foreach ($albums as $entry) {
            $album = $this->albums->create([
                'title' => $entry['title'],
                'description' => $entry['description'],
                'status' => ContentStatus::Published,
            ]);

            foreach ($entry['photos'] as $photo) {
                $this->albums->addPhoto($album, $this->source($photo['file']), $photo['properties'], preserveOriginal: true);
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $awards
     */
    private function importAwards(array $awards): void
    {
        foreach ($awards as $award) {
            $this->awards->create([
                'name' => $award['name'],
                'organisation' => $award['organisation'] ?? null,
                'year' => $award['year'],
                'description' => $award['description'] ?? null,
                'status' => ContentStatus::Published,
            ]);
        }
    }

    /**
     * @param  list<array{0: CvSection, 1: array<string, string>, 2: array<string, string>, 3: array<string, string>|null, 4: int|null, 5: int|null}>  $entries
     */
    private function importCv(array $entries): void
    {
        foreach ($entries as [$section, $title, $organisation, $description, $start, $end]) {
            $this->cvEntries->create([
                'section' => $section,
                'title' => $title,
                'organisation' => $organisation,
                'role_description' => $description,
                'start_year' => $start,
                'end_year' => $end,
                'status' => ContentStatus::Published,
            ]);
        }
    }

    /**
     * @param  list<array{quote: array<string, string>, attribution: array<string, string>}>  $quotes
     */
    private function importQuotes(array $quotes): void
    {
        foreach ($quotes as $quote) {
            $this->quotes->create([
                'quote' => $quote['quote'],
                'attribution' => $quote['attribution'],
                'status' => ContentStatus::Published,
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $papers
     */
    private function importPublications(array $papers): void
    {
        foreach ($papers as $paper) {
            $publication = $this->publications->create([
                'type' => $paper['type'],
                'title' => $paper['title'],
                'authors' => 'Mohamed Osman Khidir',
                'abstract' => $paper['abstract'],
                'status' => ContentStatus::Published,
            ]);

            $this->publications->attachFile($publication, $this->stage($paper['file']));
        }
    }

    /**
     * @param  array{courses: array<string, array<string, string>>, materials: list<array<int, mixed>>, access: TeachingAccess}  $teaching
     */
    private function importTeaching(array $teaching): void
    {
        foreach ($teaching['materials'] as [$course, $topic, $type, $title, $description, $file, $date]) {
            $material = $this->materials->create([
                'title' => $title,
                'course' => $course,
                'course_description' => $teaching['courses'][$course] ?? null,
                'topic' => $topic,
                'type' => $type,
                'description' => $description,
                'material_date' => $date,
                'access' => $teaching['access'],
                'status' => ContentStatus::Published,
            ]);

            $this->materials->attachFile($material, $this->stage('documents/'.$file));
        }
    }

    /**
     * @param  list<array<string, mixed>>  $videos
     * @param  list<array<string, mixed>>  $albums  where the poster images are described
     */
    private function importVideos(array $videos, array $albums): void
    {
        $photos = [];

        foreach ($albums as $album) {
            foreach ($album['photos'] as $photo) {
                $photos[basename($photo['file'])] = $photo;
            }
        }

        foreach ($videos as $entry) {
            $location = $entry['location'];

            $video = $this->videos->create([
                'title' => $entry['title'],
                'date' => $entry['date'],
                'event' => $entry['event'],
                'location' => $location === null ? null : [Locales::DEFAULT => $location[0], Locales::AR => $location[1]],
                'description' => $entry['description'],
                'provider' => $entry['provider'],
                'media_url' => $entry['url'] ?? null,
                'is_featured' => $entry['is_featured'] ?? false,
                'status' => ContentStatus::Published,
            ]);

            if (isset($entry['file'])) {
                $this->videos->setVideoFile($video, $this->stage($entry['file']));
            }

            $poster = $photos[$entry['thumbnail']] ?? throw new RuntimeException("No album photo describes the poster {$entry['thumbnail']}.");
            $properties = $poster['properties'];
            unset($properties[MediaProperties::IS_COVER]);

            $this->videos->setThumbnail($video, $this->stage($poster['file']), $properties);
        }
    }

    /**
     * The archive file's path; it must exist.
     */
    private function source(string $relative): string
    {
        $path = self::ARCHIVE_PATH.'/'.$relative;

        if (! is_file($path)) {
            throw new RuntimeException("Archive file missing: {$relative}");
        }

        return $path;
    }

    /**
     * A copy of the archive file, under its own name, for the media library to
     * move into place (the archive itself is never touched).
     */
    private function stage(string $relative): string
    {
        $copy = $this->staging.'/'.basename($relative);
        File::copy($this->source($relative), $copy);

        return $copy;
    }
}
