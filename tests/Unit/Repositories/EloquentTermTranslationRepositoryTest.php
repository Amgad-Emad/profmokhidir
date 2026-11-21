<?php

namespace Tests\Unit\Repositories;

use App\Constants\Locales;
use App\Constants\TermVocabulary;
use App\Models\TermTranslation;
use App\Repositories\Contracts\TermTranslationRepositoryInterface;
use App\Repositories\Eloquent\EloquentTermTranslationRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EloquentTermTranslationRepositoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_is_bound_and_groups_names_by_vocabulary_for_one_language(): void
    {
        $repository = app(TermTranslationRepositoryInterface::class);
        $this->assertInstanceOf(EloquentTermTranslationRepository::class, $repository);

        TermTranslation::factory()->naming(TermVocabulary::CommentaryTag, 'climate', 'المناخ')->create();
        TermTranslation::factory()->naming(TermVocabulary::TeachingCourse, 'Oil Crops', 'المحاصيل الزيتية')->create();
        TermTranslation::factory()->naming(TermVocabulary::TeachingCourse, 'Oil Crops', 'Cultures oléagineuses')->create(['locale' => 'fr']);

        $this->assertSame([
            TermVocabulary::CommentaryTag->value => ['climate' => 'المناخ'],
            TermVocabulary::TeachingCourse->value => ['Oil Crops' => 'المحاصيل الزيتية'],
        ], $repository->labels(Locales::AR));
    }

    #[Test]
    public function put_creates_updates_and_removes_and_counts_only_real_changes(): void
    {
        $repository = app(TermTranslationRepositoryInterface::class);

        $this->assertSame(2, $repository->put(TermVocabulary::TeachingTopic, Locales::AR, ['Lectures' => 'المحاضرات', 'Notes' => 'ملاحظات']));
        $this->assertSame(0, $repository->put(TermVocabulary::TeachingTopic, Locales::AR, ['Lectures' => 'المحاضرات']), 'An unchanged name is not a change.');
        $this->assertSame(2, $repository->put(TermVocabulary::TeachingTopic, Locales::AR, ['Lectures' => 'محاضرات', 'Notes' => null]));

        $this->assertSame([TermVocabulary::TeachingTopic->value => ['Lectures' => 'محاضرات']], $repository->labels(Locales::AR));
    }
}
