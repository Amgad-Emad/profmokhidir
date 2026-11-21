<?php

use App\Support\SearchText;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The owner's rule (21 November 2026): in Arabic the Professor is always
 * "الأستاذ الدكتور محمد عثمان خضر". Content already stored with a short form
 * ("البروفيسور خضر", "البروفيسور محمد عثمان خضر", "الأستاذ الدكتور خضر") is
 * rewritten in place, and the Arabic profile name gains the title. Wording that
 * transcribes a document (a certificate naming "محمد عثمان خضر") is left as is.
 */
return new class extends Migration
{
    private const string FULL = 'الأستاذ الدكتور محمد عثمان خضر';

    /** Tables whose rows are site content, never framework bookkeeping. */
    private const array SKIP = ['migrations', 'cache', 'cache_locks', 'sessions', 'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens'];

    public function up(): void
    {
        $display = $this->displayReplacements();
        $search = $this->searchReplacements();

        foreach (Schema::getTableListing(schema: DB::connection()->getDatabaseName(), schemaQualified: false) as $table) {
            if (in_array($table, self::SKIP, true) || ! Schema::hasColumn($table, 'id')) {
                continue;
            }

            $columns = collect(Schema::getColumns($table))
                ->filter(fn (array $column): bool => in_array($column['type_name'], ['varchar', 'text', 'mediumtext', 'longtext', 'json'], true))
                ->pluck('name');

            foreach ($columns as $column) {
                DB::table($table)->select(['id', $column])->where($column, 'like', '%خضر%')
                    ->orWhere($column, 'like', '%\\\\u062e\\\\u0636\\\\u0631%')
                    ->orderBy('id')
                    ->each(function (object $row) use ($table, $column, $display, $search): void {
                        $value = $row->{$column};
                        $replacements = $column === SearchText::COLUMN ? $search : $display;
                        $updated = $this->rewrite($value, $replacements, $table === 'profiles' && $column === 'name');

                        if ($updated !== $value) {
                            DB::table($table)->where('id', $row->id)->update([$column => $updated]);
                        }
                    });
            }
        }
    }

    /**
     * Irreversible: the short forms are not kept.
     */
    public function down(): void {}

    /** @var list<string> */
    private const array SHORT = ['البروفيسور محمد عثمان خضر', 'البروفيسور خضر', 'الأستاذ الدكتور خضر'];

    /**
     * Readable text: each short form becomes the full name. The folded spelling
     * of the full name ("الاستاذ ...", left by an earlier run of this migration on
     * a development database) is restored too.
     *
     * @return array<string, string>
     */
    private function displayReplacements(): array
    {
        $map = [SearchText::normalise(self::FULL) => self::FULL, 'ل'.SearchText::normalise(self::FULL) => 'ل'.self::FULL];

        foreach (self::SHORT as $short) {
            $map[$short] = self::FULL;
            // With the preposition "ل" the article's alef drops: للبروفيسور خضر.
            $map['ل'.mb_substr($short, 1)] = 'ل'.mb_substr(self::FULL, 1);
        }

        return $map;
    }

    /**
     * The folded search column holds the normalised spelling.
     *
     * @return array<string, string>
     */
    private function searchReplacements(): array
    {
        $map = [];

        foreach (self::SHORT as $short) {
            $map[SearchText::normalise($short)] = SearchText::normalise(self::FULL);
            $map['ل'.mb_substr(SearchText::normalise($short), 1)] = 'ل'.mb_substr(SearchText::normalise(self::FULL), 1);
        }

        return $map;
    }

    /**
     * @param  array<string, string>  $replacements
     */
    private function rewrite(string $value, array $replacements, bool $isProfileName): string
    {
        $decoded = json_decode($value, true);

        if (! is_array($decoded)) {
            return strtr($value, $replacements);
        }

        $changed = false;
        array_walk_recursive($decoded, function (mixed &$item) use ($replacements, &$changed): void {
            if (is_string($item) && ($new = strtr($item, $replacements)) !== $item) {
                $item = $new;
                $changed = true;
            }
        });

        if ($isProfileName && ($decoded['ar'] ?? null) === 'محمد عثمان خضر') {
            $decoded['ar'] = self::FULL;
            $changed = true;
        }

        return $changed ? json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : $value;
    }
};
