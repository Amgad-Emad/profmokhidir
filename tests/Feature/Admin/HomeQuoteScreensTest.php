<?php

namespace Tests\Feature\Admin;

use App\Constants\AdminNavigation;
use App\Constants\ContentStatus;
use App\Constants\ContentTypes;
use App\Constants\Disks;
use App\Constants\HomeTimeline;
use App\Livewire\Admin\HomeQuotes\Form as HomeQuoteForm;
use App\Livewire\Admin\HomeQuotes\Index as HomeQuoteIndex;
use App\Livewire\Admin\Profile\EditProfile;
use App\Models\HomeQuote;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * "Home quotes": the reorderable, draft/published list of quotes for the home
 * page interludes, and the profile's optional "students supervised" figure.
 */
class HomeQuoteScreensTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->editor = User::factory()->editor()->create();
    }

    #[Test]
    public function the_list_is_in_the_content_group_of_the_sidebar_for_editors(): void
    {
        $this->assertSame(AdminNavigation::GROUP_CONTENT, AdminNavigation::get(AdminNavigation::HOME_QUOTES)['group']);
        $this->assertSame('admin.content.home-quotes', ContentTypes::indexRoute(ContentTypes::HOME_QUOTE));

        $this->actingAs($this->editor)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('href="'.route('admin.content.home-quotes').'"', false)
            ->assertSee('Home quotes');
    }

    #[Test]
    public function editors_create_a_quote_that_is_validated_and_kept_short(): void
    {
        Livewire::actingAs($this->editor)->test(HomeQuoteForm::class)
            ->assertSet('form.status', ContentStatus::Draft->value)
            ->call('save')
            ->assertHasErrors(['form.quote' => 'required'])
            ->set('form.quote', str_repeat('a', HomeTimeline::QUOTE_MAX_LENGTH + 1))
            ->assertHasErrors(['form.quote' => 'max'])
            ->set('form.quote', 'Knowledge is the light we pass on.')
            ->set('form.attribution', '  ')
            ->set('form.source', 'Lecture notes')
            ->set('form.status', ContentStatus::Published->value)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.content.home-quotes.edit', ['homeQuote' => HomeQuote::query()->sole()->getKey()]));

        $quote = HomeQuote::query()->sole();
        $this->assertSame('Knowledge is the light we pass on.', $quote->quote);
        $this->assertNull($quote->attribution);
        $this->assertSame('Lecture notes', $quote->source);
        $this->assertTrue($quote->isPublished());

        $this->get(route('home'))->assertSee('Knowledge is the light we pass on.', false);
    }

    #[Test]
    public function the_list_reorders_publishes_and_unpublishes(): void
    {
        $a = HomeQuote::factory()->draft()->sortOrder(1)->create(['quote' => 'Alpha line']);
        $b = HomeQuote::factory()->published()->sortOrder(2)->create(['quote' => 'Beta line']);

        Livewire::actingAs($this->editor)->test(HomeQuoteIndex::class)
            ->assertSeeInOrder(['Alpha line', 'Beta line'])
            ->assertSee('draggable="true"', false)
            ->call('reorder', [$b->getKey(), $a->getKey()])
            ->assertSee('Order saved.')
            ->assertSeeInOrder(['Beta line', 'Alpha line'])
            ->call('publish', $a->getKey())
            ->call('unpublish', $b->getKey());

        $this->assertSame(['Beta line', 'Alpha line'], HomeQuote::query()->ordered()->pluck('quote')->all());
        $this->assertTrue($a->fresh()->isPublished());
        $this->assertTrue($b->fresh()->isDraft());
    }

    #[Test]
    public function members_and_guests_cannot_manage_quotes(): void
    {
        $member = User::factory()->member()->create();

        Livewire::actingAs($member)->test(HomeQuoteIndex::class)->assertForbidden();
        Livewire::actingAs($member)->test(HomeQuoteForm::class)->assertForbidden();
        $this->actingAs($member)->get(route('admin.content.home-quotes'))->assertForbidden();

        auth()->logout();
        $this->get(route('admin.content.home-quotes'))->assertRedirect(route('login'));
    }

    #[Test]
    public function the_profile_records_students_supervised_only_as_a_positive_number(): void
    {
        Storage::fake(Disks::PUBLIC);

        Livewire::actingAs($this->editor)->test(EditProfile::class)
            ->set('form.name', 'Mohamed Osman Khidir')
            ->set('form.students_supervised', '0')
            ->call('save')
            ->assertHasErrors(['form.students_supervised'])
            ->set('form.students_supervised', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull(Profile::query()->sole()->students_supervised);

        Livewire::actingAs($this->editor)->test(EditProfile::class)
            ->set('form.students_supervised', '48')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(48, Profile::query()->sole()->students_supervised);
    }
}
