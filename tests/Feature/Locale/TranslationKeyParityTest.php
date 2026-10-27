<?php

namespace Tests\Feature\Locale;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Every interface string has its Arabic: a key in lang/en.json without one in
 * lang/ar.json fails, as does an Arabic string that loses a :placeholder or
 * a group file (validation, auth...) missing a key in Arabic.
 */
class TranslationKeyParityTest extends TestCase
{
    #[Test]
    public function every_english_key_has_an_arabic_translation(): void
    {
        $english = $this->translations('en');
        $arabic = $this->translations('ar');

        $missing = array_values(array_diff(array_keys($english), array_keys($arabic)));

        $this->assertSame([], $missing, 'Keys in lang/en.json without a translation in lang/ar.json');
    }

    /**
     * Keys whose English glues a unit letter to a placeholder (":decades" is :decade + "s", ":secondss"
     * is :seconds + "s") or that pass a placeholder only English uses (the noun, where Arabic names the
     * section instead): the Arabic uses the real placeholder names.
     *
     * @var list<string>
     */
    private const array PLACEHOLDER_EXCEPTIONS = [
        ':decades',
        ':secondss',
        ':minutesm :secondss',
        ':hoursh :minutesm',
        '{1} :count :noun still holds sample text from the set-up.|[2,*] :count :noun still hold sample text from the set-up.',
    ];

    #[Test]
    public function no_arabic_translation_is_empty_or_drops_a_placeholder(): void
    {
        $problems = [];

        foreach ($this->translations('ar') as $source => $translation) {
            if (! is_string($translation) || trim($translation) === '') {
                $problems[] = 'empty: '.$source;

                continue;
            }

            if (in_array($source, self::PLACEHOLDER_EXCEPTIONS, true)) {
                continue;
            }

            preg_match_all('/:([a-z_]+)/i', $source, $expected);
            foreach (array_unique($expected[1]) as $placeholder) {
                if (! str_contains($translation, ':'.$placeholder)) {
                    $problems[] = sprintf('":%s" missing in the Arabic of "%s"', $placeholder, $source);
                }
            }
        }

        $this->assertSame([], $problems);
    }

    #[Test]
    public function every_english_group_file_has_its_arabic_counterpart_with_the_same_keys(): void
    {
        foreach (glob(lang_path('en/*.php')) as $file) {
            $arabicFile = lang_path('ar/'.basename($file));
            $this->assertFileExists($arabicFile);

            $missing = array_diff(array_keys($this->flatten(require $file)), array_keys($this->flatten(require $arabicFile)));
            $this->assertSame([], array_values($missing), 'Missing in lang/ar/'.basename($file));
        }
    }

    /**
     * @return array<string, string>
     */
    private function translations(string $locale): array
    {
        $path = lang_path($locale.'.json');
        $this->assertFileExists($path);

        return json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array<mixed>  $array
     * @return array<string, mixed>
     */
    private function flatten(array $array, string $prefix = ''): array
    {
        $flat = [];

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                // An empty English group (validation "attributes") may be filled in Arabic.
                $flat += $this->flatten($value, $prefix.$key.'.');
            } else {
                $flat[$prefix.$key] = $value;
            }
        }

        return $flat;
    }
}
