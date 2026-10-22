<?php

namespace Tests\Feature\Pages;

use App\Constants\Disks;
use App\Constants\TeachingAccess;
use App\Constants\TeachingMaterialType;
use App\Models\TeachingMaterial;
use App\Models\User;
use App\Services\TeachingMaterialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TeachingPageTest extends TestCase
{
    use RefreshDatabase;

    private TeachingMaterialService $materials;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
        Storage::fake(Disks::PRIVATE);
        $this->materials = $this->app->make(TeachingMaterialService::class);
    }

    #[Test]
    public function it_renders_an_empty_state_without_published_materials(): void
    {
        TeachingMaterial::factory()->draft()->create(['title' => 'Draft slides']);

        $this->get(route('teaching'))
            ->assertOk()
            ->assertSee('<title>Teaching Materials · ', false)
            ->assertSeeInOrder(['chapter-opening', 'Teaching', 'rule-brass', 'id="section-title"', 'Teaching Materials'], false)
            ->assertSee('Teaching materials will appear here as they are prepared for sharing.')
            ->assertDontSee('Draft slides')
            ->assertDontSee('name="topic"', false)
            ->assertDontSee('livewire', false);
    }

    #[Test]
    public function it_sets_each_course_as_a_syllabus_with_its_description_and_a_numbered_reading_list(): void
    {
        $withFile = TeachingMaterial::factory()->published()->type(TeachingMaterialType::Slides)->course('AGR 301 Crop Physiology')->create([
            'title' => 'Photosynthesis slides', 'topic' => 'Photosynthesis', 'description' => 'Lecture 3 slides.', 'version' => 'v2.1',
            'material_date' => '2019-09-01', 'external_url' => null, 'course_description' => 'How crops capture light.', 'sort_order' => 2,
        ]);
        TeachingMaterial::factory()->published()->type(TeachingMaterialType::Notes)->course('AGR 301 Crop Physiology')->create([
            'title' => 'Water relations notes', 'topic' => null, 'course_description' => null, 'sort_order' => 1,
        ]);
        $this->materials->attachFile($withFile, UploadedFile::fake()->createWithContent('slides.pptx', str_repeat('a', 640 * 1024)));

        TeachingMaterial::factory()->published()->type(TeachingMaterialType::Reading)->course('AGR 205 Principles of Agronomy')->create([
            'title' => 'Reading list', 'topic' => null, 'description' => null, 'version' => null, 'material_date' => null,
            'external_url' => 'https://library.example.edu/list',
        ]);
        TeachingMaterial::factory()->published()->type(TeachingMaterialType::Notes)->create(['course' => null, 'title' => 'Loose notes', 'topic' => 'Irrigation']);

        $response = $this->get(route('teaching'))->assertOk();

        $response
            ->assertSeeInOrder([
                'id="course-agr-205-principles-of-agronomy"', '<span class="caps">AGR</span> 205 Principles of Agronomy', '1 item',
                '<ol class="syllabus__list"', 'class="syllabus__item"', 'Reading list', 'Reading</p>', 'href="https://library.example.edu/list" rel="noopener"', 'Open · library.example.edu',
                'id="course-agr-301-crop-physiology"', '<span class="caps">AGR</span> 301 Crop Physiology', '2 items', 'rule-brass',
                'class="syllabus__description" data-rise>How crops capture light.</p>',
                'class="syllabus__item"', 'Water relations notes',
                'class="syllabus__item"', 'Photosynthesis slides', 'Lecture 3 slides.',
                'Slides · Photosynthesis · Version v2.1 · <time datetime="2019-09-01">1 September 2019</time> · PPTX · 640 KB',
                'href="'.route('teaching.download', $withFile).'" target="_blank" rel="noopener"', 'Download · PPTX · 640 KB',
                'id="course-other"', 'Other materials', 'Loose notes',
            ], false)
            ->assertSee('<label id="filter-topic-label" for="filter-topic"', false)
            ->assertSee('<option value="Irrigation">Irrigation</option>', false)
            ->assertSee('<option value="Photosynthesis">Photosynthesis</option>', false)
            ->assertSee('<option value="slides">Slides</option>', false);

        $html = $response->getContent();
        $this->assertGreaterThanOrEqual(3, substr_count($html, 'data-rise-group'));
        $this->assertSame(1, substr_count($html, 'class="syllabus__description"'), 'Only courses with a description show one');
        $this->assertStringNotContainsString('syllabus__lock', $html, 'Public materials carry no lock label');
    }

    #[Test]
    public function topic_and_type_filters_narrow_the_list_through_the_query_string(): void
    {
        TeachingMaterial::factory()->published()->type(TeachingMaterialType::Slides)->create(['title' => 'Slides A', 'topic' => 'Photosynthesis']);
        TeachingMaterial::factory()->published()->type(TeachingMaterialType::Notes)->create(['title' => 'Notes B', 'topic' => 'Photosynthesis']);
        TeachingMaterial::factory()->published()->type(TeachingMaterialType::Notes)->create(['title' => 'Notes C', 'topic' => 'Irrigation']);

        $this->get(route('teaching', ['topic' => 'Photosynthesis']))
            ->assertOk()->assertSee('Slides A')->assertSee('Notes B')->assertDontSee('Notes C')
            ->assertSee('<option value="Photosynthesis" selected>', false)
            ->assertSee('href="'.route('teaching').'" class="filter-pill">Clear filters', false);

        $this->get(route('teaching', ['topic' => 'Photosynthesis', 'type' => 'notes']))
            ->assertOk()->assertSee('Notes B')->assertDontSee('Slides A')->assertDontSee('Notes C');

        $this->get(route('teaching', ['type' => 'syllabus']))
            ->assertOk()->assertSee('No materials match these filters.')->assertDontSee('Notes B');

        $this->get(route('teaching', ['topic' => 'Unknown topic', 'type' => 'bogus']))
            ->assertOk()->assertSee('Slides A')->assertSee('Notes C');

        $this->get(route('teaching', ['topic' => str_repeat('a', 300)]))->assertRedirect(route('teaching'));
    }

    #[Test]
    public function members_only_materials_show_a_quiet_note_to_visitors_and_a_download_to_members(): void
    {
        $material = TeachingMaterial::factory()->published()->membersOnly()->create(['title' => 'Problem set 4', 'external_url' => 'https://www.example.org/set']);
        $this->materials->attachFile($material, UploadedFile::fake()->createWithContent('set.pdf', str_repeat('a', 12 * 1024)));
        $download = route('teaching.download', $material);

        $this->get(route('teaching'))
            ->assertOk()
            ->assertSeeInOrder(['class="syllabus__lock"', 'Community members', 'Problem set 4', 'class="syllabus__members">For members of the Scientific Community</p>'], false)
            ->assertSee('href="'.route('community').'" class="link-meta">Join the community', false)
            ->assertSee('Apply to join the community')
            ->assertDontSee($download, false)
            ->assertDontSee('https://www.example.org/set', false);

        $this->actingAs(User::factory()->applicant()->create())->get(route('teaching'))
            ->assertOk()->assertSee('class="link-meta">Join the community', false)->assertDontSee($download, false);

        // Members get a temporary signed link to the private file, never the bare route.
        $this->actingAs(User::factory()->member()->create())->get(route('teaching'))
            ->assertOk()
            ->assertSee('href="'.$download.'?expires=', false)
            ->assertSee('signature=', false)
            ->assertSee('Download · PDF · 12 KB')
            ->assertSee('For members of the Scientific Community')
            ->assertDontSee('href="'.$download.'"', false)
            ->assertDontSee('class="link-meta">Join the community', false)
            ->assertDontSee('Apply to join the community');

        $this->actingAs(User::factory()->editor()->create())->get(route('teaching'))
            ->assertOk()->assertSee('href="'.$download.'?expires=', false);
    }

    #[Test]
    public function downloads_are_checked_server_side_on_every_request(): void
    {
        $public = TeachingMaterial::factory()->published()->create();
        $this->materials->attachFile($public, UploadedFile::fake()->create('public.pdf', 3, 'application/pdf'));

        $members = TeachingMaterial::factory()->published()->membersOnly()->create();
        $this->materials->attachFile($members, UploadedFile::fake()->create('members.pdf', 3, 'application/pdf'));

        $draft = TeachingMaterial::factory()->draft()->create();
        $this->materials->attachFile($draft, UploadedFile::fake()->create('draft.pdf', 3, 'application/pdf'));

        $noFile = TeachingMaterial::factory()->published()->create();

        $this->get(route('teaching.download', $public))->assertOk()->assertHeader('content-disposition', 'attachment; filename="public.pdf"');
        $this->get(route('teaching.download', $members))->assertForbidden();
        $this->get(route('teaching.download', $draft))->assertForbidden();
        $this->get(route('teaching.download', $noFile))->assertNotFound();
        $this->get(route('teaching.download', 999999))->assertNotFound();

        // Members-only files are reachable only through a temporary signed link, and the
        // permission check still runs behind the signature.
        $signed = $this->materials->downloadUrl($members);
        $this->assertStringContainsString('signature=', (string) $signed);

        $this->actingAs(User::factory()->applicant()->create())->get(route('teaching.download', $members))->assertForbidden();
        $this->actingAs(User::factory()->applicant()->create())->get($signed)->assertForbidden();
        $this->actingAs(User::factory()->suspendedMember()->create())->get($signed)->assertForbidden();
        $this->actingAs(User::factory()->member()->create())->get(route('teaching.download', $members))->assertForbidden();
        $this->actingAs(User::factory()->member()->create())->get($signed)->assertOk();
        $this->actingAs(User::factory()->member()->create())->get($signed.'tampered')->assertForbidden();
        $this->actingAs(User::factory()->member()->create())->get(route('teaching.download', $draft))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(route('teaching.download', $draft))->assertOk();

        $this->travel(TeachingAccess::SIGNED_URL_MINUTES + 1)->minutes();
        $this->actingAs(User::factory()->member()->create())->get($signed)->assertForbidden();
    }

    #[Test]
    public function the_teaching_section_is_marked_current_in_the_navigation(): void
    {
        $html = $this->get(route('teaching'))->assertOk()->getContent();

        preg_match_all('/<a href="([^"]+)"[^>]*aria-current="page"[^>]*>/', $html, $matches);

        $this->assertSame([route('teaching')], array_values(array_unique($matches[1])));
    }
}
