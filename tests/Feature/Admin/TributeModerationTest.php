<?php

namespace Tests\Feature\Admin;

use App\Constants\AuditAction;
use App\Constants\TributeStatus;
use App\Livewire\Admin\Tributes\Moderate;
use App\Models\AuditLog;
use App\Models\Tribute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TributeModerationTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->editor = User::factory()->editor()->create();
    }

    #[Test]
    public function tabs_show_each_status_with_counts_and_the_email_is_visible_to_moderators(): void
    {
        Tribute::factory()->pending()->create(['name' => 'Pending Person', 'email' => 'pending@example.com']);
        Tribute::factory()->approved()->create(['name' => 'Approved Person']);
        Tribute::factory()->rejected()->create(['name' => 'Rejected Person', 'email' => null]);

        Livewire::actingAs($this->editor)->test(Moderate::class)
            ->assertSet('tab', 'pending')
            ->assertSee('data-tab="pending"', false)
            ->assertSee('Pending Person')
            ->assertSee('pending@example.com')
            ->assertDontSee('Approved Person')
            ->call('showTab', 'approved')
            ->assertSee('Approved Person')
            ->assertDontSee('Pending Person')
            ->call('showTab', 'rejected')
            ->assertSee('Rejected Person')
            ->assertSee('No email given')
            ->call('showTab', 'bogus')
            ->assertSet('tab', 'rejected');

        // The public page never shows the address.
        $this->get(route('tributes'))->assertOk()->assertDontSee('pending@example.com');
    }

    #[Test]
    public function approving_and_rejecting_single_tributes_is_audited(): void
    {
        $tribute = Tribute::factory()->pending()->create(['name' => 'Amna']);

        Livewire::actingAs($this->editor)->test(Moderate::class)
            ->call('approve', $tribute->getKey())
            ->assertSee('Tribute approved and published.')
            ->assertSee('Nothing is waiting for review.');

        $this->assertSame(TributeStatus::Approved, $tribute->fresh()->status);
        $this->assertSame($this->editor->getKey(), $tribute->fresh()->approved_by);

        Livewire::actingAs($this->editor)->test(Moderate::class, ['tab' => 'approved'])
            ->call('reject') // nothing confirmed
            ->call('confirm', 'reject', $tribute->getKey())
            ->assertSee('Reject this tribute?')
            ->call('reject')
            ->assertSee('Tribute rejected.');

        $this->assertSame(TributeStatus::Rejected, $tribute->fresh()->status);
        $this->assertSame([AuditAction::TRIBUTE_APPROVED, AuditAction::TRIBUTE_REJECTED], AuditLog::query()->orderBy('id')->pluck('action')->all());
    }

    #[Test]
    public function bulk_approve_and_reject_act_on_the_selection(): void
    {
        $tributes = Tribute::factory()->count(3)->pending()->create();

        Livewire::actingAs($this->editor)->test(Moderate::class)
            ->assertSee('0 selected')
            ->call('toggleAll')
            ->assertSee('3 selected')
            ->call('toggleAll')
            ->assertSet('selected', [])
            ->set('selected', [$tributes[0]->getKey(), $tributes[1]->getKey()])
            ->call('approveSelected')
            ->assertSee('2 tributes approved.')
            ->assertSet('selected', [])
            ->set('selected', [$tributes[2]->getKey()])
            ->call('confirmRejectSelected')
            ->assertSee('Reject the selected tributes?')
            ->call('rejectSelected')
            ->assertSee('1 tribute rejected.');

        $this->assertSame(2, Tribute::query()->withStatus(TributeStatus::Approved)->count());
        $this->assertSame(1, Tribute::query()->withStatus(TributeStatus::Rejected)->count());
    }

    #[Test]
    public function typos_can_be_fixed_and_the_original_is_kept_in_the_audit_log(): void
    {
        $tribute = Tribute::factory()->pending()->create(['name' => 'Amna', 'message' => 'He taught us wel.']);

        Livewire::actingAs($this->editor)->test(Moderate::class)
            ->assertDontSee('data-edit-form', false)
            ->call('startEditing', $tribute->getKey())
            ->assertSee('data-edit-form', false)
            ->assertSet('edit.message', 'He taught us wel.')
            ->set('edit.message', '')
            ->call('saveEdit')
            ->assertHasErrors(['edit.message'])
            ->assertSee('The tribute cannot be empty.')
            ->set('edit.message', 'He taught us well.')
            ->call('saveEdit')
            ->assertHasNoErrors()
            ->assertSee('Tribute updated.')
            ->assertSee('He taught us well.')
            ->assertDontSee('data-edit-form', false);

        $log = AuditLog::query()->where('action', AuditAction::TRIBUTE_EDITED)->sole();
        $this->assertSame('He taught us wel.', $log->meta['original']['message']);
        $this->assertSame('He taught us well.', $tribute->fresh()->message);
    }

    #[Test]
    public function only_moderators_may_use_the_screen(): void
    {
        $this->get(route('admin.tributes.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->member()->create())->get(route('admin.tributes.index'))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.tributes.index'))->assertOk();
        Livewire::actingAs(User::factory()->member()->create())->test(Moderate::class)->assertForbidden();
    }

    #[Test]
    public function an_approved_tribute_can_be_featured_and_unfeatured_from_the_approved_tab(): void
    {
        $first = Tribute::factory()->approved()->create(['name' => 'First Person']);
        $second = Tribute::factory()->approved()->create(['name' => 'Second Person']);

        $component = Livewire::actingAs($this->editor)->test(Moderate::class, ['tab' => 'approved'])
            ->assertSee('data-feature', false)
            ->assertDontSee('data-featured-badge', false)
            ->call('feature', $first->getKey())
            ->assertSee('Tribute featured. It now opens the book of remembrance.')
            ->assertSee('data-featured-badge', false)
            ->assertSee('data-unfeature', false);

        $this->assertTrue($first->fresh()->isFeatured());

        $component->call('feature', $second->getKey());
        $this->assertFalse($first->fresh()->isFeatured());
        $this->assertTrue($second->fresh()->isFeatured());

        $component->call('unfeature', $second->getKey())
            ->assertSee('Tribute no longer featured.')
            ->assertDontSee('data-featured-badge', false);

        $this->assertFalse($second->fresh()->isFeatured());
        $this->assertSame(
            [AuditAction::TRIBUTE_FEATURED, AuditAction::TRIBUTE_FEATURED, AuditAction::TRIBUTE_UNFEATURED],
            AuditLog::query()->orderBy('id')->pluck('action')->all(),
        );

        // The public page shows the featured tribute as the frontispiece while it is featured.
        $component->call('feature', $first->getKey());
        $this->get(route('tributes'))->assertOk()->assertSee('data-featured', false)->assertSeeInOrder(['frontispiece', 'First Person'], false);
    }

    #[Test]
    public function pending_tributes_cannot_be_featured(): void
    {
        $pending = Tribute::factory()->pending()->create(['name' => 'Waiting']);

        Livewire::actingAs($this->editor)->test(Moderate::class)
            ->assertDontSee('data-feature', false)
            ->call('feature', $pending->getKey())
            ->assertSee('Only an approved tribute can be featured.');

        $this->assertFalse($pending->fresh()->isFeatured());
        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function rejecting_a_featured_tribute_removes_it_from_the_featured_place(): void
    {
        $featured = Tribute::factory()->featured()->create(['name' => 'Starred']);

        Livewire::actingAs($this->editor)->test(Moderate::class, ['tab' => 'approved'])
            ->assertSee('data-featured-badge', false)
            ->call('confirm', 'reject', $featured->getKey())
            ->call('reject');

        $this->assertFalse($featured->fresh()->isFeatured());
        $this->get(route('tributes'))->assertOk()->assertDontSee('data-featured', false);
    }
}
