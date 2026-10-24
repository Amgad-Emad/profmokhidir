<?php

namespace Tests\Unit\Constants;

use App\Constants\FormGrid;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The form grid's constants match resources/css/forms.css: the 12-column row from the
 * breakpoint, a span for every width class, five subgrid rows per field and no
 * multi-column form row left on the old ad-hoc Tailwind grids.
 */
class FormGridTest extends TestCase
{
    private string $css;

    protected function setUp(): void
    {
        parent::setUp();

        $this->css = (string) file_get_contents(resource_path('css/forms.css'));
    }

    #[Test]
    public function the_row_has_twelve_columns_from_the_breakpoint_and_one_below(): void
    {
        $this->assertSame(12, FormGrid::COLUMNS);
        $this->assertStringContainsString('@media (min-width: '.(FormGrid::BREAKPOINT_PX / 16).'rem)', $this->css);
        $this->assertStringContainsString('grid-template-columns: repeat('.FormGrid::COLUMNS.', minmax(0, 1fr));', $this->css);
        $this->assertMatchesRegularExpression('/\.'.FormGrid::ROW.' \{\s*display: grid;\s*grid-template-columns: minmax\(0, 1fr\);/', $this->css);
        $this->assertStringContainsString('--form-row-gap: '.FormGrid::ROW_GAP_REM.'rem;', $this->css);
    }

    #[Test]
    public function every_width_class_spans_its_columns(): void
    {
        foreach (FormGrid::widths() as $width => $columns) {
            if ($width === FormGrid::FULL) {
                $this->assertSame('', FormGrid::widthClass($width));

                continue;
            }

            $this->assertMatchesRegularExpression('/\.'.FormGrid::ROW.' > \.'.preg_quote(FormGrid::widthClass($width), '/').' \{\s*grid-column: span '.$columns.';/', $this->css, "{$width} must span {$columns}");
        }

        $this->expectException(InvalidArgumentException::class);
        FormGrid::widthClass('fifth');
    }

    #[Test]
    public function fields_in_a_row_are_subgrids_over_five_rows_with_the_control_on_the_third(): void
    {
        $this->assertSame(5, FormGrid::ROWS_PER_FIELD);
        $this->assertStringContainsString('grid-row: span '.FormGrid::ROWS_PER_FIELD.';', $this->css);
        $this->assertStringContainsString('grid-template-rows: subgrid;', $this->css);
        $this->assertMatchesRegularExpression('/> \.form-hint \{\s*grid-area: 2 \/ 1 \/ 3 \/ -1;/', $this->css);
        $this->assertMatchesRegularExpression('/:is\(\.form-field__control, \.form-check, \.form-toggle\) \{\s*grid-area: 3 \/ 1 \/ 4 \/ -1;/', $this->css);
        $this->assertMatchesRegularExpression('/> \.form-error \{\s*grid-area: 5 \/ 1 \/ 6 \/ -1;/', $this->css);
    }

    #[Test]
    public function no_form_puts_fields_side_by_side_outside_the_form_row(): void
    {
        $offenders = [];
        $views = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views')));

        foreach ($views as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php') || str_contains($file->getPathname(), '/styleguide/')) {
                continue;
            }

            // A Tailwind multi-column grid whose next line is a form field.
            if (preg_match_all('/class="[^"]*\bgrid\b[^"]*(?:sm|md|lg):grid-cols-[^"]*"[^>]*>\s*\n\s*<x-form\./', (string) file_get_contents($file->getPathname()), $m)) {
                $offenders[] = str_replace(resource_path('views').'/', '', $file->getPathname());
            }
        }

        $this->assertSame([], $offenders, 'Use <div class="'.FormGrid::ROW.'"> with width classes for fields side by side');
    }
}
