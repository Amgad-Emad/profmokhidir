<?php

namespace Tests\Unit\Design;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Regression guards for resources/js/admin.js, found in the browser check:
 * both bugs broke the dashboard silently, with no server-side symptom.
 */
class AdminScriptTest extends TestCase
{
    private string $js;

    protected function setUp(): void
    {
        parent::setUp();

        $this->js = (string) file_get_contents(resource_path('js/admin.js'));
    }

    /**
     * Section of the script between one Alpine.data() registration and the next.
     */
    private function alpineComponent(string $name): string
    {
        $start = strpos($this->js, "Alpine.data('{$name}'");
        $this->assertNotFalse($start, "Alpine component {$name} is missing");
        $next = strpos($this->js, 'Alpine.data(', $start + 1);

        return substr($this->js, $start, $next === false ? null : $next - $start);
    }

    #[Test]
    public function the_tiptap_editor_is_kept_out_of_alpines_reactive_data(): void
    {
        // An Editor stored on `this` is wrapped in an Alpine proxy and every
        // toolbar command fails with "Applying a mismatched transaction".
        $richText = $this->alpineComponent('richText');

        $this->assertStringNotContainsString('this.editor', $richText);
        $this->assertDoesNotMatchRegularExpression('/^\s*editor\s*:/m', $richText);
        $this->assertMatchesRegularExpression('/let editor = null;/', $richText);
        $this->assertStringContainsString('editor = new Editor(', $richText);
    }

    #[Test]
    public function drag_and_drop_reads_the_row_order_from_the_component_root(): void
    {
        // dragend runs on the dragged row, so $el is that row and holds no rows:
        // the order sent to reorder() was empty and nothing was saved.
        $sortable = $this->alpineComponent('sortableRows');

        $this->assertStringContainsString("this.\$root.querySelectorAll('tr[data-id]')", $sortable);
        $this->assertStringNotContainsString('this.$el.querySelectorAll', $sortable);
        $this->assertStringContainsString('this.$wire.reorder(this.ids())', $sortable);
    }
}
