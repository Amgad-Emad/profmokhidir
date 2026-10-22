<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LightboxTest extends TestCase
{
    #[Test]
    public function it_renders_an_accessible_dialog_wired_to_the_alpine_component(): void
    {
        $html = Blade::render('<x-lightbox :label="$label" />', ['label' => 'In the "field"']);

        $this->assertMatchesRegularExpression('/<dialog\s+x-ref="dialog"\s+class="lightbox on-dark"\s+aria-label="In the &quot;field&quot;"/', $html);

        foreach ([
            '@close="onClose()"', '@keydown="onKeydown($event)"', '@click="onBackdropClick($event)"',
            '@touchstart.passive="onTouchStart($event)"', '@touchend.passive="onTouchEnd($event)"',
            'aria-live="polite" x-text="counter()"', 'x-ref="close"', '@click="close()"', '<span class="t-label">Close</span>',
            'x-ref="image"', 'class="lightbox__image"', ':src="current.src || null"', ':srcset="current.srcset || null"', 'sizes="100vw"',
            ':alt="current.alt || \'\'"', ':width="current.width || null"', ':height="current.height || null"', '@load="loaded = true"',
            '<figcaption class="lightbox__caption">', 'museum-label museum-label--ivory', 'museum-label__title" x-show="current.caption" x-text="current.caption"',
            'museum-label__meta smallcaps" x-show="current.meta" x-text="current.meta"', 'museum-label__note" x-show="current.note" x-text="current.note"',
            'class="t-label text-accent-text" x-show="current.credit"', '<span class="sr-only">Credit: </span><span x-text="current.credit"></span>', 'class="lightbox__nav"',
            '@click="prev()"', 'Previous photograph', '@click="next()"', 'Next photograph',
        ] as $fragment) {
            $this->assertStringContainsString($fragment, $html, "missing {$fragment}");
        }

        $this->assertStringContainsString('aria-label="Photographs"', Blade::render('<x-lightbox />'));
    }

    #[Test]
    public function the_scripts_handle_keyboard_focus_touch_and_reduced_motion_with_soft_fades_only(): void
    {
        $js = file_get_contents(resource_path('js/lightbox.js'));

        foreach (['showModal', 'ArrowRight', 'ArrowLeft', 'Escape', "'Tab'", 'focus()', 'returnTo', 'changedTouches', 'prefers-reduced-motion', 'lightbox-open'] as $fragment) {
            $this->assertStringContainsString($fragment, $js, "missing {$fragment}");
        }

        $this->assertDoesNotMatchRegularExpression('/scale\(|bounce|spring/i', $js);

        $css = file_get_contents(resource_path('css/media.css'));
        $this->assertStringContainsString("@import './media.css';", file_get_contents(resource_path('css/app.css')));
        $this->assertStringContainsString('.lightbox__image.is-loaded', $css);
        $this->assertStringContainsString('lightboxNote', $js);
        $this->assertStringContainsString('lightboxCredit', $js);
        $this->assertMatchesRegularExpression('/@media \(min-width: 1024px\) \{[^}]*\.lightbox__figure \{\s*flex-direction: row;/s', $css, 'The label sits beside the photograph on desktop');
        $this->assertMatchesRegularExpression('/\(hover: hover\) and \(pointer: fine\) and \(prefers-reduced-motion: no-preference\)[^{]*\{[^@]*\.photo__frame--lift \.photo-archive \{\s*transition: opacity 1200ms var\(--ease-rise\);/s', $css, 'The duotone lifts only on hover-capable pointers without reduced motion');
        $this->assertStringContainsString('transition: opacity 400ms var(--ease-rise);', $css);
        $this->assertDoesNotMatchRegularExpression('/#[0-9a-f]{3,6}\b/i', $css, 'Only brand tokens are used');
        $this->assertDoesNotMatchRegularExpression('/scale\(/', $css);

        $app = file_get_contents(resource_path('js/app.js'));
        $this->assertStringContainsString("Alpine.data('lightbox', lightbox);", $app);
        $this->assertStringContainsString("Alpine.data('videoFacade', videoFacade);", $app);
    }
}
