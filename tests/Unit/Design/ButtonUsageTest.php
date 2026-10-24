<?php

namespace Tests\Unit\Design;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Every button in the site, the chat and the admin is an <x-button>: no view
 * keeps the old pill classes, and no button carries hover or colour utilities
 * that would fight the variant's tokens (what turned "Open the chat" invisible).
 */
class ButtonUsageTest extends TestCase
{
    /** Old classes replaced by the variants. */
    private const array RETIRED = ['btn-pill-filled', 'btn-pill-outline', 'btn-pill-solid', 'btn-pill--small', 'btn-quiet', 'admin-icon-button'];

    #[Test]
    public function no_view_or_stylesheet_uses_the_retired_button_classes(): void
    {
        $hits = [];

        foreach ([...File::allFiles(resource_path('views')), ...File::files(resource_path('css')), ...File::files(resource_path('js'))] as $file) {
            foreach (self::RETIRED as $class) {
                if (preg_match('/(?<![\w-])'.preg_quote($class, '/').'(?![\w-])/', $file->getContents())) {
                    $hits[] = $file->getRelativePathname().': '.$class;
                }
            }
        }

        $this->assertSame([], $hits);
    }

    #[Test]
    public function no_button_carries_hover_or_colour_utilities(): void
    {
        $hits = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            preg_match_all('/<(x-button|button)\b(?:[^>"]|"[^"]*")*>/s', $file->getContents(), $tags);

            foreach ($tags[0] as $tag) {
                if (preg_match('/\sclass="[^"]*(?<![\w-])(?:[a-z-]+:)*(?:text|bg|border|decoration)-(?:ink|surface|fill|accent|danger|pending|success|neutral|rule|focus)[\w\/-]*/', $tag, $m)
                    || preg_match('/\sclass="[^"]*\bhover:(?:text|bg|border|underline|decoration)/', $tag, $m)) {
                    $hits[] = $file->getRelativePathname().': '.trim($m[0]);
                }
            }
        }

        $this->assertSame([], $hits);
    }

    #[Test]
    public function action_buttons_use_the_component(): void
    {
        $hits = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (preg_match_all('/<button\b[^>]*\bclass="[^"]*\b(save-bar__link|link-meta|form-file__action|chat-(?:tool|toolbar__button|clip|aa|send|action|new-pill))\b/', $file->getContents(), $m)) {
                foreach ($m[1] as $class) {
                    $hits[] = $file->getRelativePathname().': <button class="'.$class.'">';
                }
            }
        }

        $this->assertSame([], $hits);
    }
}
