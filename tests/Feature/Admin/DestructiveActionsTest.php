<?php

namespace Tests\Feature\Admin;

use App\Constants\AuditAction;
use App\Constants\ChatCopy;
use App\Constants\ConfirmDialog;
use App\Livewire\Admin\Community\Applications;
use App\Livewire\Admin\Community\Reports;
use App\Livewire\Admin\ContentIndex;
use App\Livewire\Admin\Publications\Index as PublicationIndex;
use App\Livewire\Admin\Tributes\Moderate;
use App\Livewire\Admin\Users\Index as UsersIndex;
use App\Livewire\Community\Chat\Room;
use App\Models\AuditLog;
use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\ChatReport;
use App\Models\Publication;
use App\Models\Tribute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Destructive actions look destructive: every one renders the danger button variant
 * (the outline, with the trash icon on deletes), irreversible ones ask in the danger
 * dialog (icon, title, consequence, Cancel focused, the filled danger confirm) and
 * the reversible move to trash acts at once with Undo instead.
 */
class DestructiveActionsTest extends TestCase
{
    use RefreshDatabase;

    private const string OUTLINE = 'btn btn--danger btn--small';

    private const string FILLED = 'btn btn--danger-filled';

    private User $editor;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->editor = User::factory()->editor()->create();
        $this->admin = User::factory()->admin()->create();
    }

    #[Test]
    public function move_to_trash_is_a_danger_action_with_the_trash_icon_and_no_dialog(): void
    {
        $publication = Publication::factory()->create(['title' => 'Soil and water']);

        $test = Livewire::actingAs($this->editor)->test(PublicationIndex::class);
        // The row's own action: a danger icon button (and the same in the menu, for touch screens).
        $this->assertMatchesRegularExpression('/<button type="button" class="data-table__action is-danger" title="Move to trash" wire:click="trash\('.$publication->getKey().'\)" data-delete="data-delete">\s*<svg/', $test->html());
        $test->assertSee('row-menu__item row-menu__item--danger row-menu__item--touch" wire:click="trash('.$publication->getKey().')"', false);

        $html = $test->set('selected', [$publication->getKey()])->html();

        $this->assertMatchesRegularExpression('/<button type="button" class="'.self::OUTLINE.'" wire:click="bulkTrash"[^>]*data-bulk="trash">\s*<svg[^>]*>.*?<\/svg>.*?Move to trash<\/button>/s', $html);

        Livewire::actingAs($this->editor)->test(PublicationIndex::class)
            ->call('trash', $publication->getKey())
            ->assertDontSee('data-confirm-dialog', false)
            ->assertDispatched('admin-toast');
    }

    #[Test]
    public function deleting_from_the_trash_asks_in_the_danger_dialog_with_cancel_focused(): void
    {
        $publication = Publication::factory()->create(['title' => 'Soil and water']);
        $publication->delete();

        $component = Livewire::actingAs($this->editor)->test(PublicationIndex::class)
            ->call('showTrash')
            ->assertSee('class="data-table__action is-danger" title="'.ConfirmDialog::PURGE_LABEL.'" wire:click="confirm(\''.ContentIndex::ACTION_PURGE.'\', '.$publication->getKey().')" data-purge="data-purge"', false)
            ->call('confirm', ContentIndex::ACTION_PURGE, $publication->getKey())
            ->assertSee('role="alertdialog"', false)
            ->assertSee('Delete this publication permanently?')
            ->assertSee('“Soil and water” will be removed from the trash for good, together with its files and images. '.ConfirmDialog::IRREVERSIBLE)
            ->assertSee('data-danger-icon', false)
            ->assertSee('data-cancel', false);

        $html = $component->html();
        $this->assertMatchesRegularExpression('/x-init="\$nextTick\(\(\) => \$el\.focus\(\)\)" data-cancel="data-cancel">Cancel<\/button>/', $html);
        $this->assertMatchesRegularExpression('/class="'.self::FILLED.'" wire:click="purge"[^>]*data-confirm="data-confirm">.*?<svg[^>]*>.*?<\/svg>.*?Delete permanently<\/button>/s', $html);
        $this->assertLessThan(strpos($html, 'data-confirm='), strpos($html, 'data-cancel='), 'Cancel comes first');

        $component->call('purge')->assertDontSee('data-confirm-dialog', false);

        $this->assertNull(Publication::withTrashed()->find($publication->getKey()));
        $this->assertSame(1, AuditLog::query()->where('action', AuditAction::CONTENT_PURGED)->count());
    }

    #[Test]
    public function cancelling_the_dialog_keeps_the_record_and_purge_needs_the_confirmation(): void
    {
        $publication = Publication::factory()->create();
        $publication->delete();

        Livewire::actingAs($this->editor)->test(PublicationIndex::class)
            ->call('showTrash')
            ->call('purge')
            ->call('confirm', ContentIndex::ACTION_PURGE, $publication->getKey())
            ->call('cancelConfirmation')
            ->assertDontSee('data-confirm-dialog', false)
            ->call('purge');

        $this->assertNotNull(Publication::withTrashed()->find($publication->getKey()));
    }

    #[Test]
    public function members_cannot_delete_permanently(): void
    {
        $publication = Publication::factory()->create();
        $publication->delete();

        Livewire::actingAs(User::factory()->member()->create())->test(PublicationIndex::class)->assertForbidden();
        $this->assertFalse(User::factory()->member()->create()->can('forceDelete', $publication));
        $this->assertTrue($this->editor->can('forceDelete', $publication));
    }

    #[Test]
    public function users_community_and_tribute_screens_use_the_danger_variant(): void
    {
        $staff = User::factory()->editor()->create(['name' => 'Omar Ali']);

        Livewire::actingAs($this->admin)->test(UsersIndex::class)
            ->assertSee('class="data-table__action is-danger" title="Deactivate" wire:click="confirm(\'deactivate\', '.$staff->getKey().')" data-deactivate="data-deactivate"', false)
            ->call('confirm', UsersIndex::ACTION_DEACTIVATE, $staff->getKey())
            ->assertSee('role="alertdialog"', false)
            ->assertSee('class="'.self::FILLED.'" wire:click="runConfirmed"', false);

        $applicant = User::factory()->applicant()->create(['name' => 'Huda Salih']);
        Livewire::actingAs($this->admin)->test(Applications::class)
            ->assertSee('class="data-table__action is-danger" title="Reject" wire:click="decide(\'reject\', '.$applicant->getKey().')" data-reject="data-reject"', false)
            ->call('decide', Applications::ACTION_REJECT, $applicant->getKey())
            ->assertSee('confirm-dialog--danger', false)
            ->assertSee('class="'.self::FILLED.'" wire:loading.attr="disabled" data-confirm="data-confirm">Reject', false);

        $tribute = Tribute::factory()->pending()->create(['name' => 'Salma']);
        Livewire::actingAs($this->editor)->test(Moderate::class)
            ->assertSee('class="data-table__action is-danger" title="Decline" wire:click="decline('.$tribute->getKey().')" data-reject="data-reject"', false)
            ->set('selected', [$tribute->getKey()])
            ->assertSee('class="'.self::OUTLINE.'" wire:click="rejectSelected"', false);
    }

    #[Test]
    public function chat_moderation_and_the_chat_use_the_danger_variant_and_dialogs(): void
    {
        $channel = ChatChannel::factory()->general()->create();
        $member = User::factory()->member()->create();
        $message = ChatMessage::factory()->for($channel, 'channel')->for($member, 'user')->create(['body' => 'Hello']);
        $report = ChatReport::factory()->for($message, 'message')->create();

        Livewire::actingAs($this->admin)->test(Reports::class)
            ->assertSee('class="data-table__action is-danger" title="Remove message" wire:click="confirm(\''.Reports::ACTION_DELETE.'\', '.$report->getKey().')" data-delete-message="data-delete-message"', false)
            ->assertSee('class="row-menu__item row-menu__item--danger" wire:click="confirm(\''.Reports::ACTION_SUSPEND.'\', '.$report->getKey().')" data-suspend-author="data-suspend-author"', false)
            ->call('confirm', Reports::ACTION_DELETE, $report->getKey())
            ->assertSee('Remove this message permanently?')
            ->assertSee('class="'.self::FILLED.'" wire:click="deleteMessage"', false);

        Livewire::actingAs($member)->test(Room::class)
            ->assertSee('data-delete-dialog', false)
            ->assertSee(ChatCopy::DELETE_CONFIRM)
            ->assertSee('class="'.self::FILLED.'" x-on:click="remove(deleteTarget)" data-delete-confirm', false)
            ->assertSee('class="btn btn--danger-filled" x-bind:disabled="reportSending" data-report-submit', false)
            ->assertSee('class="btn btn--danger btn--round chat-tool" x-on:click="removeAttachment(a)"', false)
            ->assertDontSee('chat-toolbar__confirm', false);
    }
}
