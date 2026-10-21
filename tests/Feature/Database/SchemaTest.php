<?php

namespace Tests\Feature\Database;

use App\Models\Award;
use App\Models\ChatMessage;
use App\Models\ChatRead;
use App\Models\ChatReport;
use App\Models\Publication;
use App\Models\Tribute;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SchemaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: list<string>}>
     */
    public static function contentTables(): array
    {
        return [
            'profiles' => ['profiles', ['name', 'full_bio', 'expertise', 'key_facts', 'contact_email_public']],
            'cv_entries' => ['cv_entries', ['section', 'title', 'organisation', 'start_year', 'end_year', 'last_updated_at']],
            'publications' => ['publications', ['title', 'slug', 'type', 'authors', 'year', 'venue_publisher', 'doi', 'citation', 'abstract', 'url']],
            'awards' => ['awards', ['name', 'organisation', 'year', 'description', 'source_url']],
            'videos' => ['videos', ['title', 'slug', 'date', 'event', 'location', 'description', 'provider', 'media_url', 'captions_url']],
            'albums' => ['albums', ['title', 'slug', 'description']],
            'teaching_materials' => ['teaching_materials', ['title', 'course', 'topic', 'type', 'description', 'version', 'material_date', 'external_url', 'access']],
            'commentaries' => ['commentaries', ['title', 'slug', 'date', 'summary', 'body', 'external_url', 'tags', 'byline']],
            'other_items' => ['other_items', ['title', 'slug', 'item_type', 'summary', 'body', 'date', 'category', 'destination_url']],
        ];
    }

    #[Test]
    #[DataProvider('contentTables')]
    public function every_content_table_has_its_fields_and_the_shared_publishing_columns(string $table, array $columns): void
    {
        $this->assertTrue(Schema::hasTable($table), "missing table {$table}");
        $this->assertTrue(Schema::hasColumns($table, [...$columns, 'status', 'published_at', 'sort_order', 'created_at', 'updated_at']));
    }

    #[Test]
    public function the_remaining_tables_exist_with_their_key_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('users', ['community_status', 'affiliation', 'academic_field', 'reason_for_joining', 'reviewed_by', 'reviewed_at', 'review_note']));
        $this->assertTrue(Schema::hasColumns('tributes', ['name', 'relationship', 'location', 'message', 'email', 'status', 'approved_by', 'approved_at', 'ip_hash', 'published_at', 'sort_order', 'is_featured', 'featured_at', 'share_card_path']));
        $this->assertTrue(Schema::hasColumns('chat_channels', ['name', 'slug', 'description', 'last_message_id']));
        $this->assertTrue(Schema::hasColumns('chat_messages', ['channel_id', 'user_id', 'client_uuid', 'body', 'version', 'edited_at', 'deleted_at']));
        $this->assertTrue(Schema::hasColumns('chat_reads', ['channel_id', 'user_id', 'last_read_message_id']));
        $this->assertTrue(Schema::hasColumns('chat_reports', ['message_id', 'reporter_id', 'reason', 'resolved_at']));
        $this->assertTrue(Schema::hasColumns('audit_logs', ['user_id', 'action', 'subject_type', 'subject_id', 'meta', 'created_at']));
        $this->assertFalse(Schema::hasColumn('audit_logs', 'updated_at'));

        foreach (['media', 'roles', 'permissions', 'model_has_roles', 'model_has_permissions', 'role_has_permissions'] as $packageTable) {
            $this->assertTrue(Schema::hasTable($packageTable), "missing package table {$packageTable}");
        }
    }

    #[Test]
    public function json_columns_and_fulltext_indexes_target_mysql_8(): void
    {
        $this->assertSame('json', Schema::getColumnType('profiles', 'expertise'));
        $this->assertSame('json', Schema::getColumnType('profiles', 'key_facts'));
        $this->assertSame('json', Schema::getColumnType('commentaries', 'tags'));
        $this->assertSame('json', Schema::getColumnType('audit_logs', 'meta'));

        foreach (['cv_entries', 'publications', 'awards', 'videos', 'albums', 'teaching_materials', 'commentaries', 'other_items', 'tributes', 'chat_messages'] as $table) {
            $fulltext = collect(Schema::getIndexes($table))->filter(fn (array $index): bool => $index['type'] === 'fulltext');
            $this->assertCount(1, $fulltext, "{$table} should have one fulltext index");
        }
    }

    #[Test]
    public function chat_messages_enforce_the_idempotency_key_and_store_utf8mb4(): void
    {
        $author = User::factory()->member()->create();
        $uuid = (string) Str::uuid();

        $message = ChatMessage::factory()->create(['user_id' => $author->id, 'client_uuid' => $uuid, 'body' => 'مرحباً 🌱']);

        $this->assertSame('مرحباً 🌱', $message->fresh()->body);

        $this->expectException(UniqueConstraintViolationException::class);

        ChatMessage::factory()->create(['channel_id' => $message->channel_id, 'user_id' => $author->id, 'client_uuid' => $uuid]);
    }

    #[Test]
    public function chat_reads_and_reports_are_unique_per_pair(): void
    {
        $read = ChatRead::factory()->create();

        try {
            ChatRead::factory()->create(['channel_id' => $read->channel_id, 'user_id' => $read->user_id]);
            $this->fail('Duplicate chat_reads row was accepted.');
        } catch (UniqueConstraintViolationException) {
            $this->addToAssertionCount(1);
        }

        $report = ChatReport::factory()->create();

        $this->expectException(UniqueConstraintViolationException::class);

        ChatReport::factory()->create(['message_id' => $report->message_id, 'reporter_id' => $report->reporter_id]);
    }

    #[Test]
    public function foreign_keys_use_sensible_on_delete_rules(): void
    {
        $admin = User::factory()->admin()->create();
        $tribute = Tribute::factory()->approved($admin)->create();
        $message = ChatMessage::factory()->create();
        $channel = $message->channel;
        $channel->forceFill(['last_message_id' => $message->id])->save();

        // Deleting the approver keeps the tribute (null on delete).
        $admin->delete();
        $this->assertNull($tribute->fresh()->approved_by);

        // Deleting a message clears the channel pointer; deleting the channel removes its messages.
        $message->forceDelete();
        $this->assertNull($channel->fresh()->last_message_id);

        $another = ChatMessage::factory()->create(['channel_id' => $channel->id]);
        $channel->delete();
        $this->assertDatabaseMissing('chat_messages', ['id' => $another->id]);
    }

    #[Test]
    public function published_scope_orders_by_sort_order_then_date_then_id(): void
    {
        $older = Publication::factory()->published()->create(['sort_order' => 2, 'year' => 1990]);
        $newer = Publication::factory()->published()->create(['sort_order' => 2, 'year' => 2010]);
        $first = Publication::factory()->published()->create(['sort_order' => 1, 'year' => 1980]);
        Publication::factory()->draft()->create(['sort_order' => 0]);

        $this->assertSame(
            [$first->id, $newer->id, $older->id],
            Publication::query()->published()->ordered()->pluck('id')->all(),
        );

        $this->assertSame(1, Award::factory()->published()->count(1)->create()->count());
        $this->assertSame(1, Award::query()->published()->count());
    }

    #[Test]
    public function tests_run_against_the_dedicated_testing_database(): void
    {
        $this->assertSame('profmokhidir_testing', DB::connection()->getDatabaseName());
    }
}
