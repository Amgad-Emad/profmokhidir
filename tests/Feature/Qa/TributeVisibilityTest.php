<?php

namespace Tests\Feature\Qa;

use App\Constants\Disks;
use App\Constants\TributeRelationship;
use App\Constants\TributeStatus;
use App\Models\Tribute;
use App\Models\User;
use App\Services\TributeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * A submitted tribute is pending until an admin approves it, and the
 * submitter's email never reaches a public page.
 */
class TributeVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private const string EMAIL = 'zq-private-address@example.com';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
        Notification::fake();
    }

    /**
     * @return list<string>
     */
    private function publicPaths(): array
    {
        return ['/', '/tributes', '/tributes/new', '/tributes/thank-you', '/sitemap.xml', '/about'];
    }

    #[Test]
    public function a_submitted_tribute_is_hidden_until_an_admin_approves_it(): void
    {
        $this->post(route('tributes.store'), [
            'name' => 'Zq Visitor',
            'relationship' => TributeRelationship::Student->value,
            'location' => 'Khartoum',
            'message' => 'Zq message text that must stay hidden until approval.',
            'email' => self::EMAIL,
            'consent' => '1',
        ])->assertRedirect(route('tributes.thanks'));

        $tribute = Tribute::query()->sole();
        $this->assertSame(TributeStatus::Pending, $tribute->status);
        $this->assertNull($tribute->published_at);

        $this->get(route('tributes'))->assertOk()->assertDontSee('Zq Visitor')->assertDontSee('Zq message');
        $this->get(route('home'))->assertOk()->assertDontSee('Zq Visitor');

        app(TributeService::class)->approve($tribute, User::factory()->admin()->create());

        $this->assertSame(TributeStatus::Approved, $tribute->fresh()->status);
        $this->assertNotNull($tribute->fresh()->published_at);

        $this->get(route('tributes'))->assertOk()->assertSee('Zq Visitor')->assertSee('Zq message text')->assertDontSee(self::EMAIL);
        $this->get(route('home'))->assertOk()->assertSee('Zq Visitor')->assertDontSee(self::EMAIL);
    }

    #[Test]
    public function rejected_tributes_never_appear_and_a_rejected_one_can_be_approved_later(): void
    {
        $tribute = Tribute::factory()->rejected()->create(['name' => 'Zq Rejected', 'email' => self::EMAIL]);

        $this->get(route('tributes'))->assertOk()->assertDontSee('Zq Rejected');

        app(TributeService::class)->approve($tribute, User::factory()->admin()->create());

        $this->get(route('tributes'))->assertOk()->assertSee('Zq Rejected')->assertDontSee(self::EMAIL);
    }

    #[Test]
    public function the_submitters_email_is_never_rendered_on_any_public_page(): void
    {
        Tribute::factory()->approved()->create(['name' => 'Zq Approved', 'email' => self::EMAIL]);
        Tribute::factory()->pending()->create(['name' => 'Zq Pending', 'email' => self::EMAIL]);
        Tribute::factory()->rejected()->create(['name' => 'Zq Rejected', 'email' => self::EMAIL]);

        foreach ($this->publicPaths() as $path) {
            $html = $this->get($path)->assertOk()->getContent();

            $this->assertStringNotContainsString(self::EMAIL, $html, "{$path} leaks the email");
            $this->assertStringNotContainsString(e(self::EMAIL), $html);
            $this->assertStringNotContainsString('mailto:'.self::EMAIL, $html);
        }

        $this->get(route('tributes'))->assertSee('Zq Approved')->assertDontSee('Zq Pending')->assertDontSee('Zq Rejected');
    }

    #[Test]
    public function the_wall_and_home_page_hide_the_email_even_when_the_message_mentions_an_address(): void
    {
        Tribute::factory()->approved()->create(['name' => 'Zq Writer', 'email' => self::EMAIL, 'message' => 'Write to me at zq-in-message@example.com if you knew him.']);

        $html = $this->get(route('tributes'))->assertOk()->getContent();

        $this->assertStringNotContainsString(self::EMAIL, $html);
        $this->assertDoesNotMatchRegularExpression('/href="mailto:/', $html, 'The wall never renders mailto links');
    }
}
