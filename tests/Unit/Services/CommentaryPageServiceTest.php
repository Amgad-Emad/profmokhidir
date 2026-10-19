<?php

namespace Tests\Unit\Services;

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
        $this->assertSame('<p>Safe</p>bad()<a>link</a>', $data['body']);
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
}
