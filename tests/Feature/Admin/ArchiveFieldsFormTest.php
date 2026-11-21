<?php

namespace Tests\Feature\Admin;

use App\Constants\CacheKeys;
use App\Livewire\Admin\Commentaries\Form as CommentaryForm;
use App\Livewire\Admin\Publications\Form as PublicationForm;
use App\Livewire\Admin\TeachingMaterials\Form as TeachingMaterialForm;
use App\Models\Commentary;
use App\Models\Publication;
use App\Models\TeachingMaterial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The fields editors use to shape the archive: a publication's topic and its
 * place in "Selected works", a commentary's epigraph, and a course description.
 */
class ArchiveFieldsFormTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function editors_mark_a_publication_as_featured_and_give_it_a_topic(): void
    {
        $editor = User::factory()->editor()->create();
        Publication::factory()->published()->topic('Soil fertility')->create();

        Livewire::actingAs($editor)->test(PublicationForm::class)
            ->assertSee('Show in Selected works')
            ->assertSee('<datalist id="publication-topics">', false)
            ->assertSee('<option value="Soil fertility"></option>', false)
            ->set('form.title', 'Principles of Dryland Agronomy')
            ->set('form.type', 'book')
            ->set('form.topic', '  Agronomy ')
            ->set('form.is_featured', true)
            ->set('form.status', 'published')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $publication = Publication::query()->where('title->en', 'Principles of Dryland Agronomy')->sole();
        $this->assertTrue($publication->is_featured);
        $this->assertSame('Agronomy', $publication->topic);

        $this->get(route('publications'))->assertOk()->assertSeeInOrder(['Selected works', 'Principles of Dryland Agronomy'], false);
        $this->assertTrue(Cache::has(CacheKeys::PUBLICATIONS_FEATURED));

        Livewire::actingAs($editor)->test(PublicationForm::class, ['publication' => $publication])
            ->assertSet('form.is_featured', true)
            ->assertSet('form.topic', 'Agronomy')
            ->set('form.is_featured', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse($publication->fresh()->is_featured);
        $this->assertFalse(Cache::has(CacheKeys::PUBLICATIONS_FEATURED), 'Saving clears the cached strip');
        $this->get(route('publications'))->assertOk()->assertDontSee('Selected works');
    }

    #[Test]
    public function the_publication_form_has_no_citation_field_and_saving_keeps_a_stored_citation(): void
    {
        $editor = User::factory()->editor()->create();
        $publication = Publication::factory()->published()->create(['citation' => 'Khidir, M. O. (1978). Kept.']);

        Livewire::actingAs($editor)->test(PublicationForm::class, ['publication' => $publication])
            ->assertSee('Source and links')
            ->assertDontSee('Citation as written')
            ->assertDontSee('BibTeX')
            ->set('form.title', 'Renamed')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Khidir, M. O. (1978). Kept.', $publication->fresh()->citation);
    }

    #[Test]
    public function publication_topics_and_the_featured_flag_are_validated(): void
    {
        Livewire::actingAs(User::factory()->editor()->create())->test(PublicationForm::class)
            ->set('form.title', 'T')
            ->set('form.topic', str_repeat('a', 101))
            ->set('form.is_featured', 'yes please')
            ->call('save')
            ->assertHasErrors(['form.topic', 'form.is_featured']);

        $this->assertSame(0, Publication::query()->count());
    }

    #[Test]
    public function editors_give_a_commentary_an_epigraph_shown_under_its_title(): void
    {
        $editor = User::factory()->editor()->create();

        Livewire::actingAs($editor)->test(CommentaryForm::class)
            ->assertSee('Epigraph')
            ->assertSee('[^1]:')
            ->set('form.title', 'On teaching')
            ->set('form.epigraph', 'The field is the first classroom.')
            ->set('form.epigraph_attribution', 'From a lecture, 1984')
            ->set('form.body', '<p>Text.[^1]</p><p>[^1]: A note.</p>')
            ->set('form.status', 'published')
            ->call('save')
            ->assertHasNoErrors();

        $commentary = Commentary::query()->sole();
        $this->assertSame('The field is the first classroom.', $commentary->epigraph);

        Livewire::actingAs($editor)->test(CommentaryForm::class, ['commentary' => $commentary])
            ->assertSet('form.epigraph', 'The field is the first classroom.')
            ->assertSet('form.epigraph_attribution', 'From a lecture, 1984');

        $this->get(route('commentary.show', $commentary->slug))
            ->assertOk()
            ->assertSeeInOrder(['chapter-opening__epigraph', 'The field is the first classroom.', 'From a lecture, 1984', 'aria-describedby="note-1"', 'A note.'], false);

        Livewire::actingAs($editor)->test(CommentaryForm::class, ['commentary' => $commentary])
            ->set('form.epigraph', str_repeat('a', 501))
            ->call('save')
            ->assertHasErrors(['form.epigraph']);
    }

    #[Test]
    public function editors_describe_a_course_on_one_of_its_materials(): void
    {
        $editor = User::factory()->editor()->create();
        $material = TeachingMaterial::factory()->published()->course('AGR 301 Crop Physiology')->create();

        Livewire::actingAs($editor)->test(TeachingMaterialForm::class, ['teachingMaterial' => $material])
            ->assertSee('Course description')
            ->set('form.course_description', 'How crops capture light, water and nutrients.')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('How crops capture light, water and nutrients.', $material->fresh()->course_description);
        $this->get(route('teaching'))->assertOk()->assertSee('How crops capture light, water and nutrients.');

        Livewire::actingAs($editor)->test(TeachingMaterialForm::class, ['teachingMaterial' => $material])
            ->set('form.course_description', str_repeat('a', 1001))
            ->call('save')
            ->assertHasErrors(['form.course_description']);
    }
}
