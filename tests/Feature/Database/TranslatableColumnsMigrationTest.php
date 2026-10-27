<?php

namespace Tests\Feature\Database;

use App\Models\Award;
use App\Models\Commentary;
use App\Models\HomeQuote;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\Profile;
use App\Models\Publication;
use App\Services\PageService;
use App\Support\SearchText;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The migration that made the content columns English/Arabic JSON, run down
 * and up again over seeded rows: down puts the English text back in the old
 * columns and indexes, up moves it into "en" again, nothing is lost. DDL
 * commits implicitly, so the tables are truncated instead of rolled back.
 */
class TranslatableColumnsMigrationTest extends TestCase
{
    use DatabaseTruncation;

    private const string MIGRATION = '2026_10_27_180309_convert_content_columns_to_translatable_json.php';

    /**
     * The rows are committed, so they are removed after the test too (the next tests roll back only their own).
     */
    protected function tearDown(): void
    {
        $this->truncateTablesForAllConnections();

        parent::tearDown();
    }

    #[Test]
    public function down_and_up_again_keep_every_english_value(): void
    {
        $publication = Publication::factory()->published()->create(['title' => 'Principles of “Dryland” Agronomy', 'abstract' => "Line one\nwith ' quotes and <em>markup</em>"]);
        $award = Award::factory()->create(['name' => 'Order of Merit', 'organisation' => null, 'description' => null]);
        $commentary = Commentary::factory()->create(['title' => 'On water', 'body' => '<p>Body</p>', 'epigraph' => null]);
        $quote = HomeQuote::factory()->create(['quote' => 'Knowledge is the light we pass on.', 'attribution' => null]);
        $profile = Profile::factory()->create(['name' => 'Mohamed Osman Khidir', 'expertise' => ['Agronomy', 'Soil'], 'key_facts' => [['label' => 'Field', 'value' => 'Agronomy']]]);
        app(PageService::class)->install();
        $block = PageBlock::query()->where('key', 'hero_label')->firstOrFail();
        $english = $block->getTranslation('value', 'en', false);

        $migration = $this->migration();
        $migration->down();

        $row = fn (string $table, int $id): object => DB::table($table)->where('id', $id)->first();
        $this->assertSame('Principles of “Dryland” Agronomy', $row('publications', $publication->id)->title);
        $this->assertSame("Line one\nwith ' quotes and <em>markup</em>", $row('publications', $publication->id)->abstract);
        $this->assertNull($row('awards', $award->id)->organisation);
        $this->assertSame('<p>Body</p>', $row('commentaries', $commentary->id)->body);
        $this->assertSame('Knowledge is the light we pass on.', $row('home_quotes', $quote->id)->quote);
        $this->assertSame(['Agronomy', 'Soil'], json_decode($row('profiles', $profile->id)->expertise, true));
        $this->assertSame($english, json_decode($row('page_blocks', $block->id)->value, true));
        $this->assertFalse(Schema::hasColumn('publications', SearchText::COLUMN));
        $this->assertSame('varchar', Schema::getColumnType('publications', 'title'));

        $migration->up();

        $this->assertSame(['en' => 'Principles of “Dryland” Agronomy'], json_decode($row('publications', $publication->id)->title, true));
        $this->assertNull($row('awards', $award->id)->organisation);
        $this->assertSame(['en' => [['label' => 'Field', 'value' => 'Agronomy']]], json_decode($row('profiles', $profile->id)->key_facts, true));
        $this->assertSame(['en' => $english], json_decode($row('page_blocks', $block->id)->value, true));
        $this->assertStringContainsString('principles of “dryland” agronomy', (string) $row('publications', $publication->id)->search_text);
        $this->assertSame('Principles of “Dryland” Agronomy', Publication::query()->find($publication->id)->title);
        $this->assertNotNull(Page::query()->where('key', 'home')->first());
    }

    private function migration(): Migration
    {
        return require database_path('migrations/'.self::MIGRATION);
    }
}
