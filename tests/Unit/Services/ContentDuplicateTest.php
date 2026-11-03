<?php

namespace Tests\Unit\Services;

use App\Constants\AuditAction;
use App\Constants\ContentStatus;
use App\Constants\Locales;
use App\Constants\MediaCollections;
use App\Models\AuditLog;
use App\Models\Publication;
use App\Models\User;
use App\Models\Video;
use App\Services\MediaService;
use App\Services\PublicationService;
use App\Services\VideoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Duplicate (a data table row action): a draft copy with its words in both
 * languages, a fresh address, the end of the display order and its files.
 */
class ContentDuplicateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_copy_is_a_draft_with_both_languages_and_a_new_address(): void
    {
        $user = User::factory()->editor()->create();
        $original = Publication::factory()->published()->featured()->create(['title' => ['en' => 'Soil survey', 'ar' => 'مسح التربة'], 'year' => 1990, 'sort_order' => 3]);

        $copy = app(PublicationService::class)->duplicate($original, $user);

        $this->assertNotSame($original->id, $copy->id);
        $this->assertSame('Soil survey (copy)', $copy->getTranslation('title', Locales::EN));
        $this->assertSame('مسح التربة (نسخة)', $copy->getTranslation('title', Locales::AR));
        $this->assertSame(ContentStatus::Draft, $copy->status);
        $this->assertNull($copy->published_at);
        $this->assertFalse((bool) $copy->is_featured);
        $this->assertSame(1990, (int) $copy->year);
        $this->assertNotSame($original->slug, $copy->slug);
        $this->assertGreaterThan($original->sort_order, $copy->sort_order);
        $this->assertSame(1, AuditLog::query()->where('action', AuditAction::CONTENT_CREATED)->where('subject_id', $copy->id)->count());
    }

    #[Test]
    public function the_copy_takes_the_files_with_their_details(): void
    {
        Storage::fake('public');
        $video = Video::factory()->create();
        app(MediaService::class)->attach($video, UploadedFile::fake()->image('poster.jpg', 800, 450), MediaCollections::THUMBNAIL, ['alt_text' => 'The poster']);

        $copy = app(VideoService::class)->duplicate($video, User::factory()->editor()->create());

        $poster = app(MediaService::class)->first($copy, MediaCollections::THUMBNAIL);
        $this->assertNotNull($poster);
        $this->assertSame('The poster', $poster->getCustomProperty('alt_text'));
        $this->assertNotNull(app(MediaService::class)->first($video->fresh(), MediaCollections::THUMBNAIL));
    }
}
