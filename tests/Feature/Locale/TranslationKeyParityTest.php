<?php

namespace Tests\Feature\Locale;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Every interface string has its Arabic: a key in lang/en.json without one in
 * lang/ar.json fails, as does an Arabic string that loses a :placeholder or
 * a group file (validation, auth...) missing a key in Arabic. The admin's own
 * views, components and services are scanned too: every string they pass to
 * __() needs its Arabic, and no English may be written straight into markup.
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

    /** Where the admin's interface strings live: its Blade views and its Livewire components and services. */
    private const array ADMIN_SOURCES = [
        'resources/views/admin',
        'resources/views/components/admin',
        'resources/views/livewire/admin',
        'app/Livewire/Admin',
    ];

    /** Admin services whose strings reach the screen (row labels, table captions). */
    private const array ADMIN_SERVICES = [
        'app/Services/DataTableService.php',
        'app/Services/RevisionService.php',
        'app/Services/AdminShellService.php',
        'app/Services/ListToolbarService.php',
    ];

    #[Test]
    public function every_string_the_admin_translates_has_an_arabic_translation(): void
    {
        $arabic = $this->translations('ar');
        $missing = [];

        foreach ($this->adminFiles() as $file) {
            preg_match_all("/(?:__|trans_choice)\\(\\s*'((?:[^'\\\\]|\\\\.)+)'/", (string) file_get_contents($file), $matches);

            foreach ($matches[1] as $key) {
                $key = stripslashes($key);

                if (preg_match('/^[a-z0-9-]+\.[a-z0-9_.]+$/', $key) === 1) {
                    if (trans($key, [], 'ar') === $key) {
                        $missing[] = $key.' ('.$this->relative($file).')';
                    }

                    continue;
                }

                if (! array_key_exists($key, $arabic)) {
                    $missing[] = $key.' ('.$this->relative($file).')';
                }
            }
        }

        $this->assertSame([], array_values(array_unique($missing)), 'Admin strings without an Arabic translation');
    }

    /**
     * Words written straight into admin markup (between tags or in a label,
     * title, placeholder or alt attribute) never reach the translator, so they
     * stay English on Arabic pages. Every such word goes through __().
     */
    #[Test]
    public function admin_views_have_no_hardcoded_english(): void
    {
        $found = [];

        foreach ($this->adminFiles() as $file) {
            if (! str_ends_with($file, '.blade.php')) {
                continue;
            }

            $source = (string) file_get_contents($file);
            // Leave out Blade comments, PHP blocks, echoes, directives with their arguments, scripts and SVG.
            $markup = preg_replace([
                '/\{\{--.*?--\}\}/s',
                '/@php\b.*?@endphp/s',
                '/<script\b.*?<\/script>/s',
                '/<svg\b.*?<\/svg>/s',
                '/\{!!.*?!!\}/s',
                '/\{\{.*?\}\}/s',
                '/@[a-zA-Z]+\s*(\((?:[^()]++|(?1))*\))?/',
            ], ' ', $source);

            preg_match_all('/>([^<>]*)</', $markup, $texts);
            preg_match_all('/\s(?:aria-label|title|placeholder|alt|label)="([^"]*)"/', $markup, $attributes);

            foreach ([...$texts[1], ...$attributes[1]] as $text) {
                $text = trim(html_entity_decode($text));

                // A word of two or more Latin letters, outside technical tokens (formats, code, sample addresses and DOIs).
                if (preg_match('/[A-Za-z]{2,}/', $text) === 1 && ! in_array($text, self::NOT_WORDS, true) && preg_match('/^[\w.-]+\(|=>|\$|::|^https?:\/\/|^\d/', $text) !== 1) {
                    $found[] = $this->relative($file).': '.$text;
                }
            }
        }

        $this->assertSame([], $found, 'Hard-coded English in the admin views; wrap it in __()');
    }

    /** Text that is the same in every language (formats, keys and a language's own name), allowed in markup as it is. */
    private const array NOT_WORDS = ['CSV', 'MK', 'PDF', 'JPEG', 'PNG', 'WebP', 'HEIC', 'DOI', 'URL', 'Ctrl', 'Esc', 'Tab', 'Enter', 'Shift', 'Alt', '⌘', 'English'];

    /**
     * @return list<string>
     */
    private function adminFiles(): array
    {
        $files = array_map(base_path(...), self::ADMIN_SERVICES);

        foreach (self::ADMIN_SOURCES as $directory) {
            foreach (File::allFiles(base_path($directory)) as $file) {
                if (in_array($file->getExtension(), ['php'], true)) {
                    $files[] = $file->getPathname();
                }
            }
        }

        return array_values(array_filter($files, 'is_file'));
    }

    private function relative(string $file): string
    {
        return ltrim(str_replace(base_path(), '', $file), '/');
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
