<?php

namespace Tests\Unit\Support;

use App\Support\DataTable\Row;
use App\Support\DataTable\RowAction;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Row actions build safe Livewire calls; rows join their supporting line and pick their inline actions.
 */
class RowActionTest extends TestCase
{
    #[Test]
    public function calls_quote_strings_and_encode_lists(): void
    {
        $this->assertSame('trash(4)', RowAction::call('trash', [4]));
        $this->assertSame("confirm('purge', 4)", RowAction::call('confirm', ['purge', 4]));
        $this->assertSame('restoreMany([4,5])', RowAction::call('restoreMany', [[4, 5]]));
        $this->assertSame("confirm('it\\'s', 1)", RowAction::call('confirm', ["it's", 1]));
        $this->assertSame('clearSelection()', RowAction::call('clearSelection'));
    }

    #[Test]
    public function rows_skip_empty_supporting_parts_and_list_inline_actions(): void
    {
        $edit = RowAction::link('Edit', 'pencil', '/edit', inline: true);
        $publish = RowAction::wire('Publish', 'check', 'publish', [1]);
        $row = new Row(1, 'Title', supporting: ['AGR 205', null, '', '605 B'], actions: [$edit, $publish]);

        $this->assertSame('AGR 205 · 605 B', $row->supportingLine());
        $this->assertSame([$edit], $row->inlineActions());
        $this->assertTrue($row->cell('missing')->isEmpty());
    }
}
