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
            ->assertSeeInOrder(['section-header', 'Teaching', 'rule-brass', 'id="section-title"', 'Teaching Materials'], false)
            ->assertSee('Teaching materials will appear here as they are prepared for sharing.')
            ->assertDontSee('Draft slides')
            ->assertDontSee('<form', false)
            ->assertDontSee('livewire', false);
    }

    #[Test]
    public function it_groups_cards_by_course_with_type_description_version_date_and_links(): void
    {
        $withFile = TeachingMaterial::factory()->published()->type(TeachingMaterialType::Slides)->course('AGR 301 Crop Physiology')->create([
            'title' => 'Photosynthesis slides', 'topic' => 'Photosynthesis', 'description' => 'Lecture 3 slides.', 'version' => 'v2.1',
            'material_date' => '2019-09-01', 'external_url' => null,
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
                'id="course-agr-205-principles-of-agronomy"', 'AGR 205 Principles of Agronomy',
                'Reading</p>', 'Reading list', 'href="https://library.example.edu/list" rel="noopener"', 'Open · library.example.edu',
                'id="course-agr-301-crop-physiology"', 'AGR 301 Crop Physiology',
                'Slides · Photosynthesis', 'Photosynthesis slides', 'Lecture 3 slides.', 'v2.1', '<time datetime="2019-09-01">1 September 2019</time>',
                'href="'.route('teaching.download', $withFile).'" target="_blank" rel="noopener"', 'Download · PPTX · 640 KB',
                'id="course-other"', 'Other materials', 'Loose notes',
            ], false)
            ->assertSee('<label for="filter-topic"', false)
            ->assertSee('<option value="Irrigation">Irrigation</option>', false)
            ->assertSee('<option value="Photosynthesis">Photosynthesis</option>', false)
            ->assertSee('<option value="slides">Slides</option>', false);

        $this->assertGreaterThanOrEqual(3, substr_count($response->getContent(), 'data-rise-group'));
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
            ->assertSee('Problem set 4')
            ->assertSee('Community members')
            ->assertSee('For community members.')
            ->assertSee('href="'.route('community').'"', false)
            ->assertSee('Apply to join the community')
            ->assertDontSee($download, false)
            ->assertDontSee('https://www.example.org/set', false);

        $this->actingAs(User::factory()->applicant()->create())->get(route('teaching'))
            ->assertOk()->assertSee('For community members.')->assertDontSee($download, false);

        // Members get a temporary signed link to the private file, never the bare route.
        $this->actingAs(User::factory()->member()->create())->get(route('teaching'))
            ->assertOk()
            ->assertSee('href="'.$download.'?expires=', false)
            ->assertSee('signature=', false)
            ->assertSee('Download · PDF · 12 KB')
            ->assertDontSee('href="'.$download.'"', false)
            ->assertDontSee('For community members.')
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
