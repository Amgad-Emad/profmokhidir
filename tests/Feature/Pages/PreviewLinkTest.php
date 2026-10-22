<?php

namespace Tests\Feature\Pages;

use App\Constants\ContentTypes;
use App\Constants\Preview;
use App\Models\Commentary;
use App\Models\Publication;
use App\Services\PreviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Signed preview links show a draft on its real public page, and only there.
 */
class PreviewLinkTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_signed_link_shows_the_draft_on_its_public_page_with_a_banner_and_no_indexing(): void
    {
        $draft = Commentary::factory()->draft()->create(['title' => 'On patience', 'slug' => 'on-patience']);
        $url = $this->app->make(PreviewService::class)->url(ContentTypes::COMMENTARY, $draft);

        $this->get(route('commentary.show', 'on-patience'))->assertNotFound();

        $this->get((string) $url)
            ->assertOk()
            ->assertSee('On patience')
            ->assertSee('data-preview-banner', false)
            ->assertSee(Preview::BANNER_TEXT)
            ->assertHeader('X-Robots-Tag', Preview::ROBOTS);

        // The preview never warms the cache visitors are served from.
        $this->get(route('commentary.show', 'on-patience'))->assertNotFound();
        $this->get(route('commentary'))->assertOk()->assertDontSee('On patience');
    }

    #[Test]
    public function a_draft_in_a_list_page_appears_only_in_the_preview(): void
    {
        $draft = Publication::factory()->draft()->create(['title' => 'Unpublished monograph']);
        $url = $this->app->make(PreviewService::class)->url(ContentTypes::PUBLICATION, $draft);

        $this->get((string) $url)->assertOk()->assertSee('Unpublished monograph');
        $this->get(route('publications'))->assertOk()->assertDontSee('Unpublished monograph')->assertDontSee('data-preview-banner', false);
    }

    #[Test]
    public function a_tampered_or_expired_link_is_refused(): void
    {
        $draft = Commentary::factory()->draft()->create(['slug' => 'on-patience']);
        $other = Commentary::factory()->draft()->create(['slug' => 'other']);
        $url = (string) $this->app->make(PreviewService::class)->url(ContentTypes::COMMENTARY, $draft);

        $this->get(str_replace('commentary.'.$draft->getKey(), 'commentary.'.$other->getKey(), $url))->assertForbidden();
        $this->get(route('commentary.show', ['slug' => 'on-patience', Preview::PARAM => 'commentary.'.$draft->getKey()]))->assertForbidden();

        $this->travel(Preview::TTL_MINUTES + 1)->minutes();
        $this->get($url)->assertForbidden();
    }
}
