<?php

namespace Tests\Unit\Services;

use App\Constants\Essay;
use App\Constants\PageSizes;
use App\Models\Commentary;
use App\Services\CommentaryPageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CommentaryPageServiceTest extends TestCase
{
    use RefreshDatabase;

    private CommentaryPageService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(CommentaryPageService::class);
    }

    #[Test]
    public function the_index_matches_tags_case_insensitively_and_builds_tag_options(): void
    {
        Commentary::factory()->published()->tagged(['Policy', 'Education'])->create();
        Commentary::factory()->published()->tagged(['Research'])->create();
        Commentary::factory()->draft()->tagged(['Secret'])->create();

        $data = $this->service->index('policy');

        $this->assertSame('Policy', $data['activeTag']);
        $this->assertSame(1, $data['commentaries']->total());
        $this->assertSame(PageSizes::COMMENTARIES, $data['commentaries']->perPage());
        $this->assertSame(['Education', 'Policy', 'Research'], array_column($data['tags'], 'value'));
        $this->assertSame([false, true, false], array_column($data['tags'], 'active'));
        $this->assertSame(route('commentary', ['tag' => 'Education']), $data['tags'][0]['url']);
        $this->assertSame(route('commentary'), $data['clearUrl']);
        $this->assertSame('commentary', $data['section']['key']);

        $unknown = $this->service->index('Secret');
        $this->assertNull($unknown['activeTag']);
        $this->assertSame(2, $unknown['commentaries']->total(), 'An unknown tag shows the full archive');
    }

    #[Test]
    public function show_sanitises_the_body_and_links_the_neighbours(): void
    {
        $newer = Commentary::factory()->published()->create(['sort_order' => 1, 'date' => '2022-01-01']);
        $current = Commentary::factory()->published()->tagged(['Policy'])->create([
            'sort_order' => 2, 'date' => '2021-01-01',
            'body' => '<p>Safe</p><script>bad()</script><a href="javascript:x" onclick="y">link</a>',
        ]);
        $older = Commentary::factory()->published()->create(['sort_order' => 3, 'date' => '2020-01-01']);

        $data = $this->service->show($current);

        $this->assertTrue($data['commentary']->is($current));
        $this->assertSame('<p>Safe</p><a>link</a>', $data['body']);
        $this->assertSame([['value' => 'Policy', 'label' => 'Policy', 'url' => route('commentary', ['tag' => 'Policy']), 'active' => false]], $data['tags']);
        $this->assertTrue($data['previous']->is($newer));
        $this->assertTrue($data['next']->is($older));
        $this->assertTrue($data['section']['active']);
    }

    #[Test]
    public function find_returns_published_commentaries_only(): void
    {
        $published = Commentary::factory()->published()->create(['slug' => 'yes']);
        Commentary::factory()->draft()->create(['slug' => 'no']);

        $this->assertTrue($this->service->find('yes')->is($published));
        $this->assertNull($this->service->find('no'));
        $this->assertNull($this->service->find('missing'));
    }

    #[Test]
    public function the_detail_page_has_the_prepared_essay_its_contents_reading_time_and_further_reading(): void
    {
        $commentary = Commentary::factory()->published()->tagged(['Education'])->create([
            'external_url' => null,
            'body' => '<p>Opening.[^1]</p><h2>First part</h2><p>Text.</p><h2>Second part</h2><p>[^1]: A note.</p>',
        ]);
        $related = Commentary::factory()->published()->tagged(['Education'])->create();

        $data = $this->service->show($commentary);

        $this->assertSame(['part-first-part', 'part-second-part'], array_column($data['contents'], 'id'));
        $this->assertSame('1 '.Essay::READING_TIME_SUFFIX, $data['readingTime']);
        $this->assertStringContainsString('aria-describedby="note-1"', $data['body']);
        $this->assertStringContainsString('<h2 id="part-first-part">First part</h2>', $data['body']);
        $this->assertStringNotContainsString('[^1]', $data['body']);
        $this->assertTrue($related->is($data['related']->first()));
    }

    #[Test]
    public function body_sidenotes_start_at_two_when_the_origin_note_takes_the_first_number(): void
    {
        $commentary = Commentary::factory()->published()->create([
            'external_url' => 'https://www.example.org/essay',
            'body' => '<p>Text.[^1]</p><p>[^1]: A note.</p>',
        ]);

        $this->assertStringContainsString('aria-describedby="note-2"', $this->service->show($commentary)['body']);
    }
}
