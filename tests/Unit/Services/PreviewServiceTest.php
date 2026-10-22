<?php

namespace Tests\Unit\Services;

use App\Constants\ContentTypes;
use App\Constants\Preview;
use App\Models\Commentary;
use App\Models\Publication;
use App\Services\PreviewService;
use App\Support\PreviewContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PreviewServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_preview_link_is_the_signed_public_address_naming_the_record(): void
    {
        $commentary = Commentary::factory()->draft()->create(['slug' => 'on-patience']);
        $url = $this->app->make(PreviewService::class)->url(ContentTypes::COMMENTARY, $commentary);

        $this->assertStringStartsWith(route('commentary.show', 'on-patience'), (string) $url);
        $this->assertStringContainsString(Preview::PARAM.'='.urlencode(Preview::value(ContentTypes::COMMENTARY, $commentary->getKey())), (string) $url);
        $this->assertTrue(Request::create((string) $url)->hasValidSignature());

        $publication = Publication::factory()->draft()->create();
        $this->assertStringStartsWith(route('publications'), (string) $this->app->make(PreviewService::class)->url(ContentTypes::PUBLICATION, $publication));
    }

    #[Test]
    public function the_published_scope_lets_only_the_previewed_draft_through(): void
    {
        $previewed = Publication::factory()->draft()->create();
        $other = Publication::factory()->draft()->create();
        $live = Publication::factory()->published()->create();

        $this->assertSame([$live->getKey()], Publication::query()->published()->pluck('id')->all());

        $this->app->make(PreviewContext::class)->activate(Publication::class, $previewed->getKey());

        $this->assertTrue($this->app->make(PreviewService::class)->active());
        $this->assertEqualsCanonicalizing([$live->getKey(), $previewed->getKey()], Publication::query()->published()->pluck('id')->all());
        $this->assertNotContains($other->getKey(), Publication::query()->published()->pluck('id')->all());
        $this->assertSame([], Commentary::query()->published()->pluck('id')->all());
    }

    #[Test]
    public function preview_values_parse_only_for_known_types(): void
    {
        $this->assertSame(['type' => ContentTypes::PUBLICATION, 'id' => 12], Preview::parse('publication.12'));
        $this->assertNull(Preview::parse('users.1'));
        $this->assertNull(Preview::parse('publication.x'));
        $this->assertNull(Preview::parse(null));
    }
}
