<?php

namespace Tests\Unit\Services;

use App\Constants\TeachingMaterialType;
use App\Models\TeachingMaterial;
use App\Models\User;
use App\Services\TeachingPageService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TeachingPageServiceTest extends TestCase
{
    use RefreshDatabase;

    private TeachingPageService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(TeachingPageService::class);
    }

    #[Test]
    public function it_lists_every_published_material_grouped_by_course_and_marks_what_the_viewer_may_download(): void
    {
        $public = TeachingMaterial::factory()->published()->course('B Course')->create();
        $members = TeachingMaterial::factory()->published()->membersOnly()->course('A Course')->create();
        TeachingMaterial::factory()->draft()->course('A Course')->create();

        $guest = $this->service->data(null);

        $this->assertSame(['A Course', 'B Course'], array_keys($guest['courses']));
        $this->assertTrue($guest['hasAny']);
        $this->assertFalse($guest['canSeeMembers']);
        $downloadable = $guest['downloadable'];
        ksort($downloadable);
        $this->assertSame([$public->id => true, $members->id => false], $downloadable);
        $this->assertSame(route('community'), $guest['communityUrl']);
        $this->assertSame(route('teaching'), $guest['clearUrl']);

        $member = $this->service->data(User::factory()->member()->create());

        $this->assertTrue($member['canSeeMembers']);
        $downloadable = $member['downloadable'];
        ksort($downloadable);
        $this->assertSame([$public->id => true, $members->id => true], $downloadable);
    }

    #[Test]
    public function topic_and_type_filters_narrow_the_courses_and_options_come_from_all_materials(): void
    {
        TeachingMaterial::factory()->published()->type(TeachingMaterialType::Slides)->course('C1')->create(['topic' => 'Photosynthesis']);
        TeachingMaterial::factory()->published()->type(TeachingMaterialType::Notes)->course('C2')->create(['topic' => 'irrigation']);
        TeachingMaterial::factory()->published()->type(TeachingMaterialType::Notes)->course('C2')->create(['topic' => ' Photosynthesis ']);

        $data = $this->service->data(null, 'Photosynthesis', 'notes');

        $this->assertSame(['C2'], array_keys($data['courses']));
        $this->assertCount(1, $data['courses']['C2']);
        $this->assertSame(['topic' => 'Photosynthesis', 'type' => TeachingMaterialType::Notes], $data['active']);
        $this->assertTrue($data['isFiltered']);
        $this->assertSame(['irrigation', 'Photosynthesis'], $data['topics']);
        $this->assertSame([TeachingMaterialType::Slides, TeachingMaterialType::Notes], $data['types']);

        $unknown = $this->service->data(null, 'Nope', 'bogus');
        $this->assertSame(['topic' => null, 'type' => null], $unknown['active']);
        $this->assertFalse($unknown['isFiltered']);
        $this->assertSame(['C1', 'C2'], array_keys($unknown['courses']));

        $none = $this->service->data(null, 'irrigation', 'slides');
        $this->assertSame([], $none['courses']);
        $this->assertTrue($none['hasAny']);
    }

    #[Test]
    public function filter_topics_and_types_work_on_any_collection(): void
    {
        $materials = new Collection([
            TeachingMaterial::factory()->make(['topic' => 'B', 'type' => TeachingMaterialType::Exercise]),
            TeachingMaterial::factory()->make(['topic' => 'a', 'type' => TeachingMaterialType::Slides]),
            TeachingMaterial::factory()->make(['topic' => null, 'type' => TeachingMaterialType::Slides]),
        ]);

        $this->assertSame(['a', 'B'], $this->service->topics($materials));
        $this->assertSame([TeachingMaterialType::Slides, TeachingMaterialType::Exercise], $this->service->types($materials));
        $this->assertCount(2, $this->service->filter($materials, null, TeachingMaterialType::Slides));
        $this->assertCount(1, $this->service->filter($materials, 'a', TeachingMaterialType::Slides));
        $this->assertCount(0, $this->service->filter($materials, 'a', TeachingMaterialType::Exercise));
    }

    #[Test]
    public function it_reports_when_nothing_is_published(): void
    {
        TeachingMaterial::factory()->draft()->create();

        $data = $this->service->data(null);

        $this->assertFalse($data['hasAny']);
        $this->assertSame([], $data['courses']);
        $this->assertSame([], $data['topics']);
        $this->assertSame([], $data['types']);
    }
}
