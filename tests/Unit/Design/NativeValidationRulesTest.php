<?php

namespace Tests\Unit\Design;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The browser's own "Please fill out this field" bubble never appears: every form
 * is printed with novalidate, the form script cancels `invalid` for forms added
 * later, and our own messages (FormFieldService, forms.js) say what to fix. The
 * server validates every form again.
 */
class NativeValidationRulesTest extends TestCase
{
    /**
     * Every <form ...> opening tag in the views, as "path: tag".
     *
     * @return list<string>
     */
    private function formTags(): array
    {
        $tags = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            preg_match_all('/<form\b(?:"[^"]*"|\'[^\']*\'|[^>"\'])*>/', $file->getContents(), $matches);

            foreach ($matches[0] as $tag) {
                $tags[] = $file->getRelativePathname().': '.preg_replace('/\s+/', ' ', $tag);
            }
        }

        return $tags;
    }

    #[Test]
    public function every_form_turns_off_native_validation(): void
    {
        $tags = $this->formTags();

        $this->assertGreaterThan(30, count($tags));
        $this->assertSame([], array_values(array_filter($tags, fn (string $tag): bool => ! preg_match('/\snovalidate[\s>]/', $tag))));
    }

    #[Test]
    public function the_form_script_cancels_the_bubble_for_every_form(): void
    {
        $script = (string) file_get_contents(resource_path('js/forms.js'));

        $this->assertStringContainsString("document.addEventListener('invalid', (event) => event.preventDefault(), true);", $script);
        $this->assertStringContainsString('form.noValidate = true;', $script);
    }

    #[Test]
    public function the_chat_image_description_is_optional(): void
    {
        $composer = (string) file_get_contents(resource_path('views/livewire/community/chat/partials/composer.blade.php'));

        preg_match('/<input type="text" class="chat-tray__alt"[^>]*>/s', $composer, $input);
        $this->assertNotEmpty($input);
        $this->assertDoesNotMatchRegularExpression('/\srequired[\s>]/', $input[0]);
    }
}
