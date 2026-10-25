<?php

namespace Tests\Feature\Admin;

use App\Constants\ContentStatus;
use App\Constants\Disks;
use App\Constants\MediaProperties;
use App\Constants\PageBlocks;
use App\Constants\Pages;
use App\Constants\Preview;
use App\Constants\RevisionKind;
use App\Livewire\Admin\Pages\Edit;
use App\Livewire\Admin\Pages\Index;
use App\Models\Page;
use App\Models\User;
use App\Services\PageService;
use App\Services\PreviewService;
use App\Services\RevisionService;
use Database\Seeders\PageContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Admin › Pages: the list, the edit screen of every page, drafts beside the
 * published wording, publishing, the guardrails, version history, images and
 * the preview of the real page with the draft.
 */
class PagesScreenTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
        $this->seed(PageContentSeeder::class);
        $this->editor = User::factory()->editor()->create();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function pages(): iterable
    {
        foreach (Pages::all() as $page) {
            yield $page => [$page];
        }
    }

    #[Test]
    public function editors_and_admins_see_every_page_in_site_order(): void
    {
        foreach ([$this->editor, User::factory()->admin()->create()] as $user) {
            $html = $this->actingAs($user)->get(route(Pages::INDEX_ROUTE))->assertOk()->getContent();

            preg_match_all('/data-page-row="([a-z]+)"/', $html, $rows);
            $this->assertSame(Pages::all(), $rows[1]);
        }

        $this->actingAs($this->editor)->get(route('admin.dashboard'))->assertSee(route(Pages::INDEX_ROUTE), false);
    }

    #[Test]
    public function members_applicants_and_guests_cannot_reach_the_pages(): void
    {
        $edit = route(Pages::EDIT_ROUTE, [Pages::ROUTE_PARAMETER => Pages::HOME]);

        $this->get($edit)->assertRedirect(route('login'));

        foreach ([User::factory()->member()->create(), User::factory()->applicant()->create(), User::factory()->create()] as $user) {
            $this->actingAs($user)->get(route(Pages::INDEX_ROUTE))->assertForbidden();
            $this->actingAs($user)->get($edit)->assertForbidden();
        }

        Livewire::actingAs(User::factory()->member()->create())->test(Edit::class, ['page' => Pages::HOME])->assertForbidden();
        Livewire::actingAs(User::factory()->member()->create())->test(Index::class)->assertForbidden();
    }

    #[Test]
    #[DataProvider('pages')]
    public function every_page_opens_with_its_blocks_labelled_in_site_order(string $page): void
    {
        $html = $this->actingAs($this->editor)->get(route(Pages::EDIT_ROUTE, [Pages::ROUTE_PARAMETER => $page]))->assertOk()->getContent();

        preg_match_all('/data-page-block="([a-z0-9_]+)"/', $html, $blocks);
        $this->assertSame(array_keys(PageBlocks::for($page)), $blocks[1]);

        foreach (PageBlocks::sections($page) as $section => $keys) {
            $this->assertStringContainsString(e($section), $html);
        }

        $this->assertStringContainsString('data-save-bar', $html);
        $this->assertStringContainsString('data-page-preview', $html);
    }

    #[Test]
    public function the_home_editor_names_its_blocks_in_plain_words_with_limits(): void
    {
        $this->actingAs($this->editor)->get(route(Pages::EDIT_ROUTE, [Pages::ROUTE_PARAMETER => Pages::HOME]))
            ->assertOk()
            ->assertSee('Hero heading, second line')
            ->assertSee('Hero tagline')
            ->assertSee('Button: Read his story')
            ->assertSee('maxlength="160"', false)
            ->assertSee('Left empty, the original wording is kept.');
    }

    #[Test]
    public function a_saved_draft_waits_beside_the_published_wording_until_it_is_published(): void
    {
        $component = Livewire::actingAs($this->editor)->test(Edit::class, ['page' => Pages::HOME])
            ->set('form.hero_tagline', 'A new tagline for the hero.')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('admin-saved');

        $this->get('/')->assertSee('Teacher, scholar and mentor.')->assertDontSee('A new tagline for the hero.');
        $this->actingAs($this->editor)->get(route(Pages::INDEX_ROUTE))->assertSee('Unpublished changes');

        $component->call('publish')->assertHasNoErrors();

        $this->get('/')->assertSee('A new tagline for the hero.')->assertDontSee('Teacher, scholar and mentor. A life devoted');
        $this->assertFalse(app(PageService::class)->hasDraft($this->page(Pages::HOME)));
    }

    #[Test]
    public function autosave_keeps_a_draft_of_a_published_page_and_never_publishes(): void
    {
        Livewire::actingAs($this->editor)->test(Edit::class, ['page' => Pages::AWARDS])
            ->set('form.title', 'Honours')
            ->call('autosave')
            ->assertDispatched('admin-saved');

        $this->assertTrue(app(PageService::class)->hasDraft($this->page(Pages::AWARDS)));
        $this->get('/awards')->assertSee('<h1 id="section-title" class="t-heading chapter-opening__title" data-rise>Awards</h1>', false);
        $this->assertSame(RevisionKind::Autosave, app(RevisionService::class)->history($this->page(Pages::AWARDS))->first()->kind);
    }

    #[Test]
    public function unpublishing_returns_visitors_to_the_original_wording_and_keeps_the_edits(): void
    {
        $component = Livewire::actingAs($this->editor)->test(Edit::class, ['page' => Pages::HOME])
            ->set('form.closing_line', 'Edited closing line.')
            ->call('publish');
        $this->get('/')->assertSee('Edited closing line.');

        $component->call('unpublish');

        $this->assertSame(ContentStatus::Draft, $this->page(Pages::HOME)->status);
        $this->get('/')->assertDontSee('Edited closing line.')->assertSee('His lessons continue in every student he taught.');
        $component->assertSet('form.closing_line', 'Edited closing line.');
    }

    #[Test]
    public function discarding_the_draft_shows_the_published_wording_again(): void
    {
        Livewire::actingAs($this->editor)->test(Edit::class, ['page' => Pages::HOME])
            ->set('form.scroll_cue', 'Onward')
            ->call('save')
            ->call('discardDraft')
            ->assertSet('form.scroll_cue', 'Scroll');

        $this->assertFalse(app(PageService::class)->hasDraft($this->page(Pages::HOME)));
    }

    #[Test]
    public function required_blocks_cannot_be_emptied_and_show_the_original_instead(): void
    {
        Livewire::actingAs($this->editor)->test(Edit::class, ['page' => Pages::HOME])
            ->set('form.hero_heading_second', '   ')
            ->set('form.chapters_title', '')
            ->call('publish')
            ->assertHasNoErrors()
            ->assertSet('form.hero_heading_second', 'Mohamed Osman Khidir')
            ->assertSet('form.chapters_title', 'A life in chapters');

        $this->get('/')->assertSee('Mohamed Osman Khidir')->assertSee('A life in chapters');
    }

    #[Test]
    public function an_optional_block_left_empty_shows_nothing(): void
    {
        Livewire::actingAs($this->editor)->test(Edit::class, ['page' => Pages::HOME])
            ->set('form.hero_epigraph', '')
            ->call('publish')
            ->assertHasNoErrors();

        $this->get('/')->assertOk()->assertDontSee('hero__epigraph', false);
    }

    #[Test]
    public function length_limits_protect_the_layout(): void
    {
        Livewire::actingAs($this->editor)->test(Edit::class, ['page' => Pages::HOME])
            ->set('form.hero_label', str_repeat('x', 31))
            ->assertHasErrors(['form.hero_label' => 'max'])
            ->set('form.chapters_intro', '<p>'.str_repeat('y', 241).'</p>')
            ->call('save')
            ->assertHasErrors(['form.hero_label', 'form.chapters_intro']);

        $this->assertFalse(app(PageService::class)->hasDraft($this->page(Pages::HOME)));
    }

    #[Test]
    public function rich_text_is_sanitised_before_it_is_stored_and_shown(): void
    {
        Livewire::actingAs($this->editor)->test(Edit::class, ['page' => Pages::COMMUNITY])
            ->set('form.lead', '<p onclick="steal()">A <strong>private</strong> circle.<script>alert(document.cookie)</script> <a href="javascript:alert(1)">x</a> <a href="https://example.org">site</a></p><h2>Gone</h2>')
            ->call('publish')
            ->assertHasNoErrors();

        $stored = (string) app(PageService::class)->liveValues($this->page(Pages::COMMUNITY))['lead'];
        $this->assertSame('A <strong>private</strong> circle. <a>x</a> <a href="https://example.org" rel="noopener">site</a>Gone', $stored);

        $html = $this->get('/community')->assertOk()->getContent();
        $this->assertStringNotContainsString('<script>alert(document.cookie)', $html);
        $this->assertStringNotContainsString('alert(document.cookie)', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('onclick="steal()"', $html);
        $this->assertStringContainsString('A <strong>private</strong> circle.', $html);
    }

    #[Test]
    public function short_text_is_escaped_never_rendered_as_markup(): void
    {
        Livewire::actingAs($this->editor)->test(Edit::class, ['page' => Pages::AWARDS])
            ->set('form.empty', '<script>alert(1)</script>')
            ->call('publish');

        $this->get('/awards')->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    #[Test]
    public function links_go_only_to_listed_pages_or_real_web_addresses(): void
    {
        Livewire::actingAs($this->editor)->test(Edit::class, ['page' => Pages::HOME])
            ->set('form.button_story.route', 'admin.dashboard')
            ->assertHasErrors(['form.button_story.route'])
            ->set('form.button_story.route', 'publications')
            ->set('form.button_story.url', 'javascript:alert(1)')
            ->assertHasErrors(['form.button_story.url'])
            ->set('form.button_story.url', '')
            ->set('form.button_story.label', 'See his work')
            ->call('publish')
            ->assertHasNoErrors();

        $this->get('/')->assertSee('href="'.route('publications').'" class="btn', false)->assertSee('See his work');
    }

    #[Test]
    public function the_conduct_rules_can_be_reordered_added_and_removed(): void
    {
        $rules = array_column(PageBlocks::get(Pages::COMMUNITY, 'conduct')->default, 'text');

        Livewire::actingAs($this->editor)->test(Edit::class, ['page' => Pages::COMMUNITY])
            ->call('moveItem', 'conduct', 1, -1)
            ->call('addItem', 'conduct')
            ->set('form.conduct.5.text', 'Cite your sources.')
            ->call('removeItem', 'conduct', 4)
            ->call('publish')
            ->assertHasNoErrors();

        $html = $this->get('/community')->getContent();
        preg_match('/<ol[^>]*data-conduct>(.*?)<\/ol>/s', $html, $list);
        preg_match_all('/<span>([^<]+)<\/span><\/li>/', $list[1], $items);
        $this->assertSame([$rules[1], $rules[0], $rules[2], $rules[3], 'Cite your sources.'], array_map(fn ($rule) => html_entity_decode($rule, ENT_QUOTES), $items[1]));
    }

    #[Test]
    public function header_chapters_can_be_renamed_and_reordered_but_not_removed(): void
    {
        Livewire::actingAs($this->editor)->test(Edit::class, ['page' => Pages::NAVIGATION])
            ->set('form.chapters.1.label', 'Writing')
            ->call('moveItem', 'chapters', 4, -1)
            ->call('removeItem', 'chapters', 0)
            ->call('addItem', 'chapters')
            ->set('form.sections.2.label', 'Papers')
            ->call('publish')
            ->assertHasNoErrors();

        $html = $this->get('/about')->getContent();
        preg_match('/<nav class="site-chapters[^>]*>(.*?)<\/nav>/s', $html, $nav);
        preg_match_all('/class="chapter-link t-label"[^>]*>([^<]+)</', $nav[1], $labels);
        $this->assertSame(['Life', 'Writing', 'Archive', 'Tributes', 'Community'], $labels[1]);
        $this->assertStringContainsString('<span class="mega-menu__link-label">Papers</span>', $html);
        $this->assertStringContainsString('class="footer-link" >Publications</a>', $html);
    }

    #[Test]
    public function footer_links_are_chosen_from_the_list_of_pages(): void
    {
        Livewire::actingAs($this->editor)->test(Edit::class, ['page' => Pages::NAVIGATION])
            ->set('form.footer_links.0.route', '/admin')
            ->assertHasErrors(['form.footer_links.0.route'])
            ->set('form.footer_links', [['label' => 'Read his story', 'route' => 'about'], ['label' => 'Search', 'route' => 'search']])
            ->call('publish')
            ->assertHasNoErrors();

        preg_match('/<footer.*<\/footer>/s', $this->get('/cv')->getContent(), $footer);
        preg_match_all('/class="footer-link"[^>]*>([^<]+)<\/a>/', $footer[0], $links);
        $this->assertSame(['Read his story', 'Search'], $links[1]);
    }

    #[Test]
    public function an_earlier_version_can_be_restored_and_saved(): void
    {
        $component = Livewire::actingAs($this->editor)->test(Edit::class, ['page' => Pages::HOME])
            ->set('form.hero_label', 'First version')->call('publish')
            ->set('form.hero_label', 'Second version')->call('publish');

        $history = app(RevisionService::class)->history($this->page(Pages::HOME));
        $first = $history->first(fn ($revision) => ($revision->data['hero_label'] ?? null) === 'First version');

        $component->call('toggleHistory')
            ->assertSee('Version history')
            ->call('restoreRevision', $first->getKey())
            ->assertSet('form.hero_label', 'First version')
            ->assertDispatched('admin-changed')
            ->call('publish');

        $this->get('/')->assertSee('First version')->assertDontSee('Second version');
        $this->assertSame(RevisionKind::Publish, app(RevisionService::class)->history($this->page(Pages::HOME))->first()->kind);
    }

    #[Test]
    public function the_hero_image_is_uploaded_needs_its_details_and_shows_once_published(): void
    {
        $component = Livewire::actingAs($this->editor)->test(Edit::class, ['page' => Pages::HOME])
            ->set('uploads.hero_image', UploadedFile::fake()->image('hero.jpg', 800, 1000))
            ->assertHasNoErrors();

        $mediaId = $component->get('form.hero_image.media_id');
        $this->assertNotNull($mediaId);

        $component->call('publish')->assertHasErrors(['form.hero_image.alt_text', 'form.hero_image.caption', 'form.hero_image.place', 'form.hero_image.year']);
        $this->get('/')->assertDontSee('hero.jpg');

        $component->set('form.hero_image.alt_text', 'Professor Khidir at his desk')
            ->set('form.hero_image.caption', 'At the faculty')
            ->set('form.hero_image.place', 'Khartoum')
            ->set('form.hero_image.year', 1985)
            ->set('form.hero_image.focus_x', 30)
            ->set('form.hero_image.focus_y', 20)
            ->call('publish')
            ->assertHasNoErrors();

        $this->get('/')->assertSee('alt="Professor Khidir at his desk"', false)->assertSee('object-position: 30% 20%', false);
        $this->assertSame('At the faculty', $this->page(Pages::HOME)->blocks()->where('key', 'hero_image')->first()->media()->find($mediaId)->getCustomProperty(MediaProperties::CAPTION));
    }

    #[Test]
    public function uploads_are_checked_as_images(): void
    {
        Livewire::actingAs($this->editor)->test(Edit::class, ['page' => Pages::HOME])
            ->set('uploads.hero_image', UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'))
            ->assertHasErrors(['uploads.hero_image'])
            ->assertSet('form.hero_image.media_id', null);
    }

    #[Test]
    public function the_preview_shows_the_draft_on_the_real_page_and_visitors_never_see_it(): void
    {
        Livewire::actingAs($this->editor)->test(Edit::class, ['page' => Pages::HOME])
            ->set('form.hero_tagline', 'Draft tagline in preview.')
            ->call('save');

        $url = app(PreviewService::class)->pageUrl($this->page(Pages::HOME));
        $this->assertStringContainsString(Preview::PARAM.'='.Preview::PAGE.'.', urldecode($url));

        $this->get($url)->assertOk()->assertSee('Draft tagline in preview.')->assertHeader('X-Robots-Tag', Preview::ROBOTS);
        $this->get('/')->assertDontSee('Draft tagline in preview.');
        $this->get(str_replace('signature=', 'signature=x', $url))->assertForbidden();
    }

    #[Test]
    public function the_error_pages_are_previewed_in_the_dashboard_with_their_drafts(): void
    {
        Livewire::actingAs($this->editor)->test(Edit::class, ['page' => Pages::ERRORS])
            ->set('form.title_404', 'Lost in the stacks')
            ->call('save');

        $preview = route(Pages::ERROR_PREVIEW_ROUTE, [Pages::ROUTE_PARAMETER => Pages::ERRORS, 'code' => 404]);

        $this->actingAs($this->editor)->get($preview)->assertOk()->assertSee('Lost in the stacks')->assertSee('data-error-code="404"', false);
        $this->get('/this-page-does-not-exist')->assertNotFound()->assertDontSee('Lost in the stacks')->assertSee('Page not found');
        $this->actingAs(User::factory()->member()->create())->get($preview)->assertForbidden();
    }

    #[Test]
    public function the_privacy_page_can_be_previewed_before_analytics_is_switched_on(): void
    {
        $this->get('/privacy')->assertNotFound();
        $this->get(app(PreviewService::class)->pageUrl($this->page(Pages::PRIVACY)))->assertOk()->assertSee('How this site counts its readers');
    }

    #[Test]
    public function one_block_can_be_put_back_to_its_original_wording(): void
    {
        Livewire::actingAs($this->editor)->test(Edit::class, ['page' => Pages::HOME])
            ->set('form.scroll_cue', 'Down')
            ->assertSee('Use the original wording')
            ->call('restoreDefault', 'scroll_cue')
            ->assertSet('form.scroll_cue', 'Scroll');
    }

    private function page(string $key): Page
    {
        return app(PageService::class)->findByKey($key);
    }
}
