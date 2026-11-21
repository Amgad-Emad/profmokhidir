<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\AuditAction;
use App\Constants\CacheKeys;
use App\Constants\Locales;
use App\Constants\TermVocabulary;
use App\Models\User;
use App\Repositories\Contracts\TermTranslationRepositoryInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * Names in other languages for the short terms editors use to group content
 * (Other Information categories and types, teaching courses and topics,
 * commentary and publication topics). The stored term stays the English key
 * for grouping, filters and addresses; label() gives the reader its name in
 * the page's language, or the English when no translation has been written.
 */
class TermTranslationService
{
    /** @var array<string, array<string, array<string, string>>> per-request copy, by locale */
    private array $loaded = [];

    public function __construct(
        private readonly TermTranslationRepositoryInterface $terms,
        private readonly AuditLogService $audit,
        private readonly CacheRepository $cache,
        private readonly OtherItemService $otherItems,
        private readonly TeachingMaterialService $materials,
        private readonly CommentaryService $commentaries,
        private readonly PublicationService $publications,
    ) {}

    /**
     * The term's name in the given language (default: the current one),
     * falling back to its English wording; null for a blank term.
     */
    public function label(TermVocabulary $vocabulary, ?string $term, ?string $locale = null): ?string
    {
        $term = trim((string) $term);

        if ($term === '') {
            return null;
        }

        $locale ??= app()->getLocale();

        if ($locale !== Locales::DEFAULT) {
            $translated = $this->labels($locale)[$vocabulary->value][$term] ?? null;

            if (filled($translated)) {
                return $translated;
            }
        }

        return $vocabulary->english($term);
    }

    /**
     * Each term mapped to its name in the current language, for filter options.
     *
     * @param  list<string>  $terms
     * @return array<string, string>
     */
    public function options(TermVocabulary $vocabulary, array $terms): array
    {
        $options = [];

        foreach ($terms as $term) {
            $options[$term] = (string) $this->label($vocabulary, $term);
        }

        return $options;
    }

    /**
     * Stored names in one language: vocabulary value => term => name.
     *
     * @return array<string, array<string, string>>
     */
    public function labels(string $locale): array
    {
        return $this->loaded[$locale] ??= $this->cache->remember(
            CacheKeys::TERM_TRANSLATIONS.$locale,
            CacheKeys::TTL_SECONDS,
            fn (): array => $this->terms->labels($locale),
        );
    }

    /**
     * The terms each vocabulary uses in content today, for the editing screen.
     *
     * @return list<string>
     */
    public function termsInUse(TermVocabulary $vocabulary): array
    {
        $terms = match ($vocabulary) {
            TermVocabulary::OtherCategory => $this->otherItems->distinct('category'),
            TermVocabulary::OtherType => $this->otherItems->distinct('item_type'),
            TermVocabulary::TeachingCourse => $this->materials->distinct('course'),
            TermVocabulary::TeachingTopic => $this->materials->distinct('topic'),
            TermVocabulary::CommentaryTag => $this->commentaries->tags(),
            TermVocabulary::PublicationTopic => $this->publications->distinct('topic'),
        };

        $terms = array_values(array_unique(array_filter(array_map(fn ($term): string => trim((string) $term), $terms), fn (string $term): bool => $term !== '')));
        sort($terms, SORT_NATURAL | SORT_FLAG_CASE);

        return $terms;
    }

    /**
     * Every vocabulary with the terms in use and their names in one language,
     * for the editing screen. Vocabularies with no terms are left out.
     *
     * @return list<array{vocabulary: TermVocabulary, terms: array<string, string>}>
     */
    public function forEditing(string $locale = Locales::AR): array
    {
        $stored = $this->terms->labels($locale);
        $groups = [];

        foreach (TermVocabulary::cases() as $vocabulary) {
            $terms = [];

            foreach ($this->termsInUse($vocabulary) as $term) {
                $terms[$term] = $stored[$vocabulary->value][$term] ?? '';
            }

            if ($terms !== []) {
                $groups[] = ['vocabulary' => $vocabulary, 'terms' => $terms];
            }
        }

        return $groups;
    }

    /**
     * Save names in one language: vocabulary value => term => name (blank removes it).
     *
     * @param  array<string, array<string, string|null>>  $names
     */
    public function save(array $names, string $locale = Locales::AR, ?User $actor = null): int
    {
        $changed = 0;

        foreach ($names as $vocabulary => $labels) {
            $changed += $this->terms->put(TermVocabulary::from((string) $vocabulary), $locale, $labels);
        }

        if ($changed > 0) {
            $this->audit->record($actor, AuditAction::TERMS_UPDATED, null, ['locale' => $locale, 'changed' => $changed]);
        }

        $this->flushCache($locale);

        return $changed;
    }

    public function flushCache(?string $locale = null): void
    {
        foreach ($locale === null ? Locales::prefixed() : [$locale] as $code) {
            $this->cache->forget(CacheKeys::TERM_TRANSLATIONS.$code);
            unset($this->loaded[$code]);
        }
    }
}
