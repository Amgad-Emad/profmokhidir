<?php

use App\Support\SearchText;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * English and Arabic content: the language-specific columns of every content
 * table and of the page blocks become JSON maps of locale => value
 * (spatie/laravel-translatable), with every existing value moved into "en".
 * Fulltext indexes cannot cover JSON, so each searchable table gets one
 * folded search column (App\Support\SearchText) holding the text of every
 * language, with its own fulltext index. down() puts the English values back
 * in the original columns and restores the old indexes.
 */
return new class extends Migration
{
    /**
     * Table => column => [original type, length or null, nullable]. "json"
     * columns were JSON already (lists, page block values).
     *
     * @return array<string, array<string, array{0: string, 1: int|null, 2: bool}>>
     */
    private function columns(): array
    {
        return [
            'profiles' => [
                'name' => ['string', 255, false], 'title' => ['string', 255, true], 'affiliation' => ['string', 255, true],
                'short_bio' => ['text', null, true], 'full_bio' => ['longText', null, true],
                'expertise' => ['json', null, true], 'key_facts' => ['json', null, true], 'contact_note' => ['text', null, true],
            ],
            'cv_entries' => ['title' => ['string', 255, false], 'organisation' => ['string', 255, true], 'role_description' => ['text', null, true]],
            'publications' => ['title' => ['string', 500, false], 'abstract' => ['text', null, true]],
            'awards' => ['name' => ['string', 255, false], 'organisation' => ['string', 255, true], 'description' => ['text', null, true]],
            'videos' => ['title' => ['string', 255, false], 'event' => ['string', 255, true], 'location' => ['string', 255, true], 'description' => ['text', null, true]],
            'albums' => ['title' => ['string', 255, false], 'description' => ['text', null, true]],
            'teaching_materials' => ['title' => ['string', 255, false], 'course_description' => ['text', null, true], 'description' => ['text', null, true]],
            'commentaries' => ['title' => ['string', 255, false], 'summary' => ['text', null, true], 'epigraph' => ['text', null, true], 'body' => ['longText', null, true]],
            'other_items' => ['title' => ['string', 255, false], 'summary' => ['text', null, true], 'body' => ['longText', null, true]],
            'home_quotes' => ['quote' => ['text', null, false], 'attribution' => ['string', 255, true]],
            'page_blocks' => ['value' => ['json', null, true], 'draft_value' => ['json', null, true]],
        ];
    }

    /**
     * Searchable tables: the columns of their old fulltext index, and every
     * column the new search column is built from.
     *
     * @return array<string, array{fulltext: list<string>, search: list<string>}>
     */
    private function searchable(): array
    {
        return [
            'cv_entries' => ['fulltext' => ['title', 'organisation', 'role_description'], 'search' => ['title', 'organisation', 'role_description']],
            'publications' => ['fulltext' => ['title', 'authors', 'venue_publisher', 'abstract'], 'search' => ['title', 'authors', 'venue_publisher', 'abstract']],
            'awards' => ['fulltext' => ['name', 'organisation', 'description'], 'search' => ['name', 'organisation', 'description']],
            'videos' => ['fulltext' => ['title', 'event', 'location', 'description'], 'search' => ['title', 'event', 'location', 'description']],
            'albums' => ['fulltext' => ['title', 'description'], 'search' => ['title', 'description']],
            'teaching_materials' => ['fulltext' => ['title', 'course', 'topic', 'description'], 'search' => ['title', 'course', 'topic', 'description']],
            'commentaries' => ['fulltext' => ['title', 'summary'], 'search' => ['title', 'summary']],
            'other_items' => ['fulltext' => ['title', 'summary'], 'search' => ['title', 'summary']],
        ];
    }

    public function up(): void
    {
        foreach ($this->searchable() as $table => $definition) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropFullText($definition['fulltext']));
        }

        foreach ($this->columns() as $table => $columns) {
            foreach ($columns as $column => [$type, $length, $nullable]) {
                if ($type !== 'json') {
                    // Widen first, so the wrapped value fits whatever the old length was.
                    Schema::table($table, fn (Blueprint $blueprint) => $blueprint->longText($column)->nullable($nullable)->change());
                    DB::table($table)->whereNotNull($column)->update([$column => DB::raw("JSON_OBJECT('en', `{$column}`)")]);
                    Schema::table($table, fn (Blueprint $blueprint) => $blueprint->json($column)->nullable($nullable)->change());

                    continue;
                }

                DB::table($table)->whereNotNull($column)->update([$column => DB::raw("JSON_OBJECT('en', `{$column}`)")]);
            }
        }

        foreach ($this->searchable() as $table => $definition) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->longText(SearchText::COLUMN)->nullable();
                $blueprint->fullText(SearchText::COLUMN);
            });

            $this->fillSearchColumn($table, $definition['search']);
        }
    }

    public function down(): void
    {
        foreach ($this->searchable() as $table => $definition) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->dropFullText([SearchText::COLUMN]);
                $blueprint->dropColumn(SearchText::COLUMN);
            });
        }

        foreach ($this->columns() as $table => $columns) {
            foreach ($columns as $column => [$type, $length, $nullable]) {
                $english = "JSON_EXTRACT(`{$column}`, '$.en')";

                if ($type === 'json') {
                    DB::table($table)->whereNotNull($column)->update([$column => DB::raw($english)]);

                    continue;
                }

                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->longText($column)->nullable($nullable)->change());

                $value = "CASE WHEN {$english} IS NULL OR JSON_TYPE({$english}) = 'NULL' THEN ".($nullable ? 'NULL' : "''")." ELSE JSON_UNQUOTE({$english}) END";
                DB::table($table)->whereNotNull($column)->update([$column => DB::raw($value)]);

                Schema::table($table, function (Blueprint $blueprint) use ($column, $type, $length, $nullable): void {
                    $definition = match ($type) {
                        'string' => $blueprint->string($column, $length ?? 255),
                        'text' => $blueprint->text($column),
                        default => $blueprint->longText($column),
                    };

                    $definition->nullable($nullable)->change();
                });
            }
        }

        foreach ($this->searchable() as $table => $definition) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->fullText($definition['fulltext']));
        }
    }

    /**
     * @param  list<string>  $columns
     */
    private function fillSearchColumn(string $table, array $columns): void
    {
        DB::table($table)->select(['id', ...$columns])->orderBy('id')->chunkById(200, function ($rows) use ($table, $columns): void {
            foreach ($rows as $row) {
                $values = [];

                foreach ($columns as $column) {
                    $value = $row->{$column};
                    $decoded = is_string($value) && str_starts_with(ltrim($value), '{') ? json_decode($value, true) : null;
                    $values[] = is_array($decoded) ? $decoded : $value;
                }

                DB::table($table)->where('id', $row->id)->update([SearchText::COLUMN => SearchText::from($values)]);
            }
        });
    }
};
