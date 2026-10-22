<?php

namespace Tests\Unit\Constants;

use App\Constants\AdminNavigation;
use App\Constants\AdminUx;
use App\Constants\Placeholders;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminUxTest extends TestCase
{
    #[Test]
    public function every_navigation_item_has_an_icon_the_icon_component_draws(): void
    {
        $icons = (string) file_get_contents(resource_path('views/components/icon.blade.php'));

        foreach (array_merge(['dashboard'], array_keys(AdminNavigation::items())) as $key) {
            $this->assertArrayHasKey($key, AdminUx::icons(), "No icon for {$key}");
            $this->assertStringContainsString("@case('".AdminUx::icon($key)."')", $icons);
        }
    }

    #[Test]
    public function toasts_with_undo_stay_for_the_undo_window(): void
    {
        $this->assertSame(AdminUx::TOAST_SECONDS, AdminUx::toast('Saved.')['seconds']);
        $this->assertSame(AdminUx::UNDO_SECONDS, AdminUx::toast('Moved.', AdminUx::TOAST_SUCCESS, ['component' => 'x', 'method' => 'restoreMany', 'args' => [1]])['seconds']);
        $this->assertSame(10, AdminUx::UNDO_SECONDS);
        $this->assertSame(30, AdminUx::AUTOSAVE_SECONDS);
    }

    #[Test]
    public function the_shortcuts_sheet_lists_the_promised_keys(): void
    {
        $keys = array_map(fn (array $shortcut): string => implode('+', $shortcut['keys']), AdminUx::shortcuts());

        $this->assertSame(['Ctrl+K', '/', 'Ctrl+S', 'Ctrl+Enter', '?', 'Esc'], $keys);
    }

    #[Test]
    public function placeholder_copy_is_found_in_text_and_lists(): void
    {
        $this->assertTrue(Placeholders::contains(Placeholders::MARKER.' Biography'));
        $this->assertTrue(Placeholders::contains(['Soil', [Placeholders::MARKER.' fact']]));
        $this->assertFalse(Placeholders::contains('Real text'));
        $this->assertFalse(Placeholders::contains(null));
    }
}
