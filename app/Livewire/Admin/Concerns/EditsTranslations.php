<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Concerns;

use App\Constants\ContentTypes;
use App\Constants\Locales;
use App\Models\Concerns\HasTranslatableContent;
use Illuminate\Database\Eloquent\Model;

/**
 * English and Arabic editing for ContentForm. $form holds the English text of
 * the translatable columns under their own names (so the Form Request rules
 * apply unchanged) and the other languages under
 * $form['translations'][locale][column]. Both are read per language without
 * fallback, saved as locale maps (['en' => , 'ar' => ]), and an empty
 * translation is stored as null so visitors see the English text.
 */
trait EditsTranslations
{
    /**
     * A translation follows the English field's rules but is never required:
     * left empty, visitors see the English text.
     *
     * @param  list<mixed>  $fieldRules
     * @return list<mixed>
     */
    private function translationRules(array $fieldRules): array
    {
        $kept = array_values(array_filter($fieldRules, fn ($rule): bool => is_string($rule)
            && $rule !== 'nullable' && ! str_starts_with($rule, 'required')));

        return ['nullable', ...$kept];
    }

    /**
     * Prefixed key of a field in another language ("form.translations.ar.title").
     */
    protected function translationKey(string $locale, string $field): string
    {
        return self::FIELD_PREFIX.self::TRANSLATIONS.'.'.$locale.'.'.$field;
    }

    /**
     * Whether a rules key ("title", "key_facts.*.label") belongs to a translatable column.
     */
    private function isTranslatableKey(string $field): bool
    {
        return in_array(explode('.', $field, 2)[0], $this->translatableFields(), true);
    }

    /**
     * The form fields kept in English and Arabic (the model's translatable
     * columns that this form edits), in form order.
     *
     * @return list<string>
     */
    protected function translatableFields(): array
    {
        $modelClass = ContentTypes::get($this->type())['model'];

        if (! in_array(HasTranslatableContent::class, class_uses_recursive($modelClass), true)) {
            return [];
        }

        $translatable = (new $modelClass)->getTranslatableAttributes();

        return array_values(array_filter(array_keys($this->defaults()), fn (string $field): bool => in_array($field, $translatable, true)));
    }

    /**
     * Defaults plus an empty translation of every translatable field.
     *
     * @param  array<string, mixed>  $form
     * @return array<string, mixed>
     */
    protected function withTranslationDefaults(array $form): array
    {
        $defaults = $this->defaults();

        foreach (Locales::prefixed() as $locale) {
            foreach ($this->translatableFields() as $field) {
                $form[self::TRANSLATIONS][$locale][$field] = is_array($defaults[$field] ?? null) ? [] : null;
            }
        }

        return $form;
    }

    /**
     * What the service stores from validated form values: the English values
     * transformed as before, and each translatable column as a locale map
     * with its translations (empty ones as null, so they fall back).
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function dataFrom(array $validated): array
    {
        $translations = is_array($validated[self::TRANSLATIONS] ?? null) ? $validated[self::TRANSLATIONS] : [];
        unset($validated[self::TRANSLATIONS]);

        $data = $this->transform($validated);
        $translated = [];

        foreach (Locales::prefixed() as $locale) {
            $translated[$locale] = $this->transformTranslation(is_array($translations[$locale] ?? null) ? $translations[$locale] : [], $locale);
        }

        foreach ($this->translatableFields() as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $map = [Locales::DEFAULT => $data[$field]];

            foreach (Locales::prefixed() as $locale) {
                $value = $translated[$locale][$field] ?? null;
                $map[$locale] = blank($value) ? null : $value;
            }

            $data[$field] = $map;
        }

        return $data;
    }

    /**
     * Turn one language's validated translations into stored values. Override
     * alongside transform() for fields that are reshaped (lists).
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function transformTranslation(array $validated, string $locale): array
    {
        return $this->nullifyBlank($validated);
    }

    /**
     * A translatable column's own value in one language (no fallback): a list
     * for list fields, otherwise the text or null.
     */
    protected function translationOf(Model $record, string $field, string $locale, mixed $default = null): mixed
    {
        /** @var Model&HasTranslatableContent $record */
        $value = $record->getTranslation($field, $locale, false);

        if (is_array($default)) {
            return is_array($value) ? array_values($value) : [];
        }

        return $value === '' ? null : $value;
    }
}
