<?php

namespace Tests\Unit\Constants;

use App\Constants\Components;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The component catalogue on the style guide covers every Blade component in
 * resources/views/components, and nothing that does not exist.
 */
class ComponentsTest extends TestCase
{
    /**
     * @return list<string>
     */
    private function viewsOnDisk(): array
    {
        $root = resource_path('views/components');
        $views = [];

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root)) as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $views[] = str_replace([$root.'/', '.blade.php'], '', $file->getPathname());
            }
        }

        sort($views);

        return $views;
    }

    #[Test]
    public function every_component_view_has_a_catalogue_entry_and_every_entry_has_a_view(): void
    {
        $catalogued = Components::views();
        sort($catalogued);

        $this->assertSame([], array_values(array_diff($this->viewsOnDisk(), $catalogued)), 'Components without a catalogue entry');
        $this->assertSame([], array_values(array_diff($catalogued, $this->viewsOnDisk())), 'Catalogue entries without a view');
        $this->assertSame($catalogued, array_values(array_unique($catalogued)), 'Each view is catalogued once');
    }

    #[Test]
    public function every_entry_names_its_tag_group_purpose_and_usage(): void
    {
        foreach (Components::all() as $component) {
            $this->assertSame(['tag', 'view', 'group', 'purpose', 'usage'], array_keys($component));
            $this->assertStringStartsWith('x-', $component['tag']);
            $this->assertArrayHasKey($component['group'], Components::groups());
            $this->assertNotSame('', trim($component['purpose']));
            $this->assertNotSame('', trim($component['usage']));
            $this->assertSame($component['tag'], 'x-'.str_replace('/', '.', $component['view']));
        }
    }

    #[Test]
    public function by_group_keeps_the_display_order_and_loses_nothing(): void
    {
        $grouped = Components::byGroup();

        $this->assertSame(array_keys(Components::groups()), array_keys($grouped));
        $this->assertCount(count(Components::all()), array_merge(...array_values($grouped)));

        foreach ($grouped as $group => $components) {
            $this->assertNotEmpty($components, "Group {$group} has no components");
        }
    }
}
