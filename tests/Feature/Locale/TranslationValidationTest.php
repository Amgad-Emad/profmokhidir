<?php

namespace Tests\Feature\Locale;

use App\Constants\AuthScreens;
use App\Constants\AuthStatus;
use App\Constants\FormControls;
use App\Constants\Locales;
use App\Constants\ScriptStrings\FormScripts;
use App\Services\FormFieldService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The Arabic language files: the PHP groups mirror the English ones key for key, the
 * validator speaks Arabic with Arabic field names, and the shared form controls and
 * sign-in copy translate at the point of output.
 */
class TranslationValidationTest extends TestCase
{
    /**
     * @return list<string>
     */
    private function keysOf(array $lines, string $prefix = ''): array
    {
        $keys = [];

        foreach ($lines as $key => $value) {
            if ($prefix === '' && $key === 'attributes') {
                continue;
            }

            $keys = is_array($value) ? [...$keys, ...$this->keysOf($value, $prefix.$key.'.')] : [...$keys, $prefix.$key];
        }

        return $keys;
    }

    #[Test]
    public function every_english_language_line_has_an_arabic_one(): void
    {
        foreach (['auth', 'pagination', 'passwords', 'validation'] as $group) {
            $english = $this->keysOf(require lang_path("en/{$group}.php"));
            $arabic = $this->keysOf(require lang_path("ar/{$group}.php"));

            $this->assertSame([], array_values(array_diff($english, $arabic)), "lang/ar/{$group}.php misses lines");

            foreach ($english as $key) {
                $line = trans("{$group}.{$key}", [], Locales::AR);
                preg_match_all('/:[a-z_]+/', (string) trans("{$group}.{$key}", [], Locales::EN), $placeholders);
                foreach ($placeholders[0] as $placeholder) {
                    $this->assertStringContainsString($placeholder, $line, "{$group}.{$key} keeps {$placeholder}");
                }
            }
        }
    }

    #[Test]
    public function validation_messages_read_in_arabic_with_arabic_field_names(): void
    {
        $this->app->setLocale(Locales::AR);

        $errors = Validator::make(
            ['email' => 'not-an-email', 'name' => '', 'form' => ['title' => ''], 'year' => 'abc'],
            ['email' => 'email', 'name' => 'required', 'form.title' => 'required', 'year' => 'integer'],
        )->errors();

        $this->assertSame('أدخل عنوان بريد إلكتروني بالصيغة name@example.com.', $errors->first('email'));
        $this->assertSame('أدخل الاسم.', $errors->first('name'));
        $this->assertSame('أدخل العنوان.', $errors->first('form.title'));
        $this->assertSame('أدخل سنة بين '.FormControls::YEAR_MIN.' و'.FormControls::yearMax().'.', $errors->first('year'));

        $this->app->setLocale(Locales::EN);
        $this->assertSame(FormControls::yearMessage(), Validator::make(['year' => 'abc'], ['year' => 'integer'])->errors()->first('year'));
    }

    #[Test]
    public function the_fragments_keep_every_placeholder_of_the_source(): void
    {
        $fragment = json_decode((string) file_get_contents(lang_path('ar.json')), true);

        foreach ([AuthScreens::PASSWORD_HINT, FormControls::COUNTER_FORMAT, FormControls::MESSAGE_CODE, FormControls::TAGS_REMOVE, FormControls::UPLOAD_PROGRESS, ...FormScripts::all()] as $source) {
            $this->assertArrayHasKey($source, $fragment, "Arabic for [{$source}]");
            preg_match_all('/:[a-z]+/', $source, $placeholders);
            foreach ($placeholders[0] as $placeholder) {
                $this->assertStringContainsString($placeholder, $fragment[$source]);
            }
        }
    }

    #[Test]
    public function copy_constants_translate_at_runtime(): void
    {
        $this->app->setLocale(Locales::AR);

        $this->assertSame('تسجيل الدخول', AuthScreens::screen(AuthScreens::LOGIN)['title']);
        $this->assertSame('قوية', AuthScreens::passwordStrength(64)['label']);
        $this->assertSame('تم تسجيل خروجك.', AuthStatus::message(AuthStatus::SIGNED_OUT));
        $this->assertSame('غامق', FormControls::editorTools()['bold']['label']);

        $forms = app(FormFieldService::class);
        $this->assertSame('مطلوب', $forms->requirement(true));
        $this->assertSame('اختياري', $forms->requirement(false));
        $this->assertSame('ltr', $forms->direction('email'));
        $this->assertSame('auto', $forms->direction('text'));

        $this->app->setLocale(Locales::EN);
        $this->assertSame('Sign in', AuthScreens::screen(AuthScreens::LOGIN)['title']);
        $this->assertNull($forms->direction('email'), 'Left-to-right pages keep the browser default');
    }

    #[Test]
    public function the_shared_form_controls_render_in_arabic(): void
    {
        $this->app->setLocale(Locales::AR);

        $html = Blade::render('<x-form.input name="title" label="العنوان" required /><x-form.textarea name="summary" label="الملخص" maxlength="120" /><x-form.toggle name="featured" label="مميز" /><x-form.upload name="file" label="الملف" />');

        $this->assertStringContainsString('مطلوب', $html);
        $this->assertStringContainsString('data-msg-missing="أدخل العنوان."', $html);
        $this->assertStringContainsString('الأحرف: 0 من 120', $html);
        $this->assertStringContainsString('data-format="الأحرف: :count من :max"', $html);
        $this->assertStringContainsString('dir="auto"', $html);
        $this->assertStringContainsString('مفعّل', $html);
        $this->assertStringContainsString('اسحب ملفًا إلى هنا، أو', $html);

        $editor = Blade::render('<x-form.editor name="body" label="النص" />');
        $this->assertStringContainsString('aria-label="غامق"', $editor);
        $this->assertStringContainsString('إضافة رابط', $editor);
        $this->assertStringContainsString('\\u0022dir\\u0022:\\u0022auto\\u0022', $editor);
    }
}
