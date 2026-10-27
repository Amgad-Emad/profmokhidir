<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Constants\Locales;
use App\Support\SearchText;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Spatie\Translatable\HasTranslations;

/**
 * English and Arabic content (spatie/laravel-translatable). The model lists
 * its language-specific columns in $translatable; reading one gives the
 * current language, falling back to English when there is no translation.
 * The first listed column is the primary one: a record "has" a language when
 * that column is filled in it (the sitemap and the fallback note use this).
 * A record "needs" a language when any column filled in English is empty in
 * it (the admin indicator, filter and launch checklist). Models with
 * $searchColumns keep the folded search column (App\Support\SearchText) in
 * step on every save.
 */
trait HasTranslatableContent
{
    use HasTranslations {
        HasTranslations::getAttributeValue as translatedAttributeValue;
        HasTranslations::setAttribute as translatedSetAttribute;
    }

    public static function bootHasTranslatableContent(): void
    {
        static::saving(function (self $model): void {
            $columns = $model->searchColumns();

            if ($columns === []) {
                return;
            }

            $values = [];

            foreach ($columns as $column) {
                $values[] = $model->isTranslatableAttribute($column) ? $model->getTranslations($column) : $model->getAttribute($column);
            }

            $model->setAttribute(SearchText::COLUMN, SearchText::from($values));
        });
    }

    /**
     * The current language's value, else English; null rather than an empty
     * string when neither is filled (views test for null).
     */
    public function getAttributeValue($key): mixed
    {
        $value = $this->translatedAttributeValue($key);

        return $value === '' && $this->isTranslatableAttribute($key) ? null : $value;
    }

    /**
     * Writes go to English unless they name their languages: a plain value
     * (`$model->title = 'x'`, fill(), create()) is the English text whatever
     * the current language is, and a locale map (['en' => , 'ar' => ]) sets
     * each language. Arabic is only ever written on purpose.
     */
    public function setAttribute($key, $value)
    {
        if (! $this->isTranslatableAttribute($key)) {
            return $this->translatedSetAttribute($key, $value);
        }

        // The stored map is written directly: spatie's setTranslation() re-reads the map without
        // empty strings, which would lose an English value emptied on purpose when Arabic is set too.
        $stored = json_decode((string) ($this->attributes[$key] ?? ''), true);
        $stored = is_array($stored) && ! array_is_list($stored) ? $stored : [];
        $stored = array_replace($stored, Locales::isLocaleMap($value) ? $value : [Locales::DEFAULT => $value]);

        // Nothing left in any language: the column is NULL again (not {"en": null}).
        $this->attributes[$key] = array_filter($stored, fn (mixed $item): bool => $item !== null) === []
            ? null
            : json_encode($stored, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $this;
    }

    /**
     * Whether a value is a map of the site's languages to values.
     */
    public static function isLocaleMap(mixed $value): bool
    {
        return Locales::isLocaleMap($value);
    }

    /**
     * Columns the site search matches (folded into the search column).
     *
     * @return list<string>
     */
    public function searchColumns(): array
    {
        return property_exists($this, 'searchColumns') ? $this->searchColumns : [];
    }

    public function primaryTranslatable(): string
    {
        return $this->getTranslatableAttributes()[0];
    }

    /**
     * Whether the record exists in the language (its primary column is filled there).
     */
    public function hasLocale(string $locale): bool
    {
        return $this->filledIn($this->primaryTranslatable(), $locale);
    }

    /**
     * Whether a column has a value of its own in the language (no fallback).
     */
    public function filledIn(string $column, string $locale): bool
    {
        return filled($this->getTranslation($column, $locale, false));
    }

    /**
     * Whether visitors in the current language see the English fallback of this record.
     */
    public function showsFallback(?string $locale = null): bool
    {
        $locale ??= app()->getLocale();

        return $locale !== Locales::DEFAULT && ! $this->hasLocale($locale) && $this->hasLocale(Locales::DEFAULT);
    }

    /**
     * The language a column is actually shown in for the current page: the
     * current one when it has a value there, else English (the fallback).
     */
    public function shownLocale(?string $column = null): string
    {
        $locale = app()->getLocale();

        return $this->filledIn($column ?? $this->primaryTranslatable(), $locale) ? $locale : Locales::DEFAULT;
    }

    /**
     * Columns filled in English but empty in the language.
     *
     * @return list<string>
     */
    public function missingTranslations(string $locale = Locales::AR): array
    {
        return array_values(array_filter(
            $this->getTranslatableAttributes(),
            fn (string $column): bool => $this->filledIn($column, Locales::DEFAULT) && ! $this->filledIn($column, $locale),
        ));
    }

    public function needsTranslation(string $locale = Locales::AR): bool
    {
        return $this->missingTranslations($locale) !== [];
    }

    /**
     * Records with a column filled in English but empty in the language.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function needingTranslation(Builder $query, string $locale = Locales::AR): void
    {
        $query->where(function (Builder $query) use ($locale): void {
            foreach ($this->getTranslatableAttributes() as $column) {
                $query->orWhere(function (Builder $query) use ($column, $locale): void {
                    $query->whereRaw(self::filledSql($this->qualifyColumn($column), Locales::DEFAULT))
                        ->whereRaw('NOT '.self::filledSql($this->qualifyColumn($column), $locale));
                });
            }
        });
    }

    /**
     * Records that exist in the language (primary column filled there).
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function translatedInto(Builder $query, string $locale): void
    {
        $query->whereRaw(self::filledSql($this->qualifyColumn($this->primaryTranslatable()), $locale));
    }

    /**
     * SQL true when the JSON column holds a non-empty value for the language
     * (a non-empty string, or a non-empty list or object).
     */
    private static function filledSql(string $column, string $locale): string
    {
        if (! Locales::isValid($locale)) {
            throw new InvalidArgumentException(sprintf('Unknown locale "%s".', $locale));
        }

        $wrapped = implode('.', array_map(fn (string $part): string => '`'.$part.'`', explode('.', $column)));
        $path = "JSON_EXTRACT({$wrapped}, '$.\"{$locale}\"')";

        return "COALESCE(CASE JSON_TYPE({$path})"
            ." WHEN 'STRING' THEN CHAR_LENGTH(TRIM(JSON_UNQUOTE({$path}))) > 0"
            ." WHEN 'ARRAY' THEN JSON_LENGTH({$path}) > 0"
            ." WHEN 'OBJECT' THEN JSON_LENGTH({$path}) > 0"
            .' ELSE FALSE END, FALSE)';
    }
}
