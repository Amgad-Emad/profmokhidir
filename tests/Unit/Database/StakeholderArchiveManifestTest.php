<?php

namespace Tests\Unit\Database;

use App\Constants\MediaProperties;
use Database\Seeders\StakeholderArchiveSeeder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The archive manifest describes every curated file, in both languages, and
 * names only files that exist.
 */
class StakeholderArchiveManifestTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $manifest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manifest = StakeholderArchiveSeeder::manifest();
    }

    #[Test]
    public function every_file_named_in_the_manifest_exists_and_every_archive_file_is_used(): void
    {
        $named = [$this->manifest['portrait']['file']];

        foreach ($this->manifest['albums'] as $album) {
            foreach ($album['photos'] as $photo) {
                $named[] = $photo['file'];
            }
        }

        foreach ($this->manifest['publications'] as $paper) {
            $named[] = $paper['file'];
        }

        foreach ($this->manifest['teaching']['materials'] as $material) {
            $named[] = 'documents/'.$material[5];
        }

        foreach ($this->manifest['videos'] as $video) {
            if (isset($video['file'])) {
                $named[] = $video['file'];
            }

            $named[] = 'photos/'.$video['thumbnail'];
        }

        foreach (array_unique($named) as $file) {
            $this->assertFileExists(StakeholderArchiveSeeder::ARCHIVE_PATH.'/'.$file);
        }

        $onDisk = [];

        foreach (['photos', 'documents', 'videos'] as $folder) {
            foreach (glob(StakeholderArchiveSeeder::ARCHIVE_PATH.'/'.$folder.'/*') ?: [] as $path) {
                $onDisk[] = $folder.'/'.basename($path);
            }
        }

        $this->assertSame([], array_values(array_diff($onDisk, $named)), 'Every curated file appears somewhere on the site.');
    }

    #[Test]
    public function every_photo_has_a_caption_and_alt_text_in_english_and_arabic(): void
    {
        foreach ($this->manifest['albums'] as $album) {
            $this->assertNotEmpty($album['title']['en']);
            $this->assertNotEmpty($album['title']['ar']);

            foreach ($album['photos'] as $photo) {
                $properties = $photo['properties'];
                $arabic = $properties[MediaProperties::TRANSLATIONS]['ar'];

                $this->assertNotEmpty($properties[MediaProperties::CAPTION], $photo['file']);
                $this->assertNotEmpty($properties[MediaProperties::ALT_TEXT], $photo['file']);
                $this->assertNotEmpty($arabic[MediaProperties::CAPTION], $photo['file']);
                $this->assertNotEmpty($arabic[MediaProperties::ALT_TEXT], $photo['file']);
                $this->assertSame(isset($properties[MediaProperties::PLACE]), isset($arabic[MediaProperties::PLACE]), $photo['file'].' has its place in both languages or neither.');
            }
        }
    }

    #[Test]
    public function every_text_record_is_written_in_english_and_arabic(): void
    {
        $maps = [];

        foreach ($this->manifest['awards'] as $award) {
            $maps[] = $award['name'];
        }

        foreach ($this->manifest['cv'] as $entry) {
            $maps[] = $entry[1];
        }

        foreach ($this->manifest['publications'] as $paper) {
            $maps[] = $paper['title'];
            $maps[] = $paper['abstract'];
        }

        foreach ($this->manifest['teaching']['materials'] as $material) {
            $maps[] = $material[3];
            $maps[] = $material[4];
        }

        foreach ($this->manifest['videos'] as $video) {
            $maps[] = $video['title'];
        }

        foreach ($maps as $map) {
            $this->assertNotEmpty($map['en'] ?? null);
            $this->assertNotEmpty($map['ar'] ?? null, $map['en'] ?? '');
        }
    }
}
