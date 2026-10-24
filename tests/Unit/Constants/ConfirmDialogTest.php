<?php

namespace Tests\Unit\Constants;

use App\Constants\ConfirmDialog;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConfirmDialogTest extends TestCase
{
    #[Test]
    public function the_purge_title_names_the_type_in_lower_case(): void
    {
        $this->assertSame('Delete this publication permanently?', ConfirmDialog::purgeTitle('Publication'));
        $this->assertSame('Delete this cv entry permanently?', ConfirmDialog::purgeTitle('CV entry'));
    }

    #[Test]
    public function the_purge_body_says_what_goes_and_that_it_cannot_be_undone(): void
    {
        $body = ConfirmDialog::purgeBody('Soil and water');

        $this->assertStringStartsWith('“Soil and water” will be removed', $body);
        $this->assertStringEndsWith(ConfirmDialog::IRREVERSIBLE, $body);
    }

    #[Test]
    public function the_delete_icon_is_one_the_icon_component_draws(): void
    {
        $this->assertStringContainsString("@case('".ConfirmDialog::DELETE_ICON."')", (string) file_get_contents(resource_path('views/components/icon.blade.php')));
        $this->assertStringContainsString("@case('warning')", (string) file_get_contents(resource_path('views/components/icon.blade.php')));
    }
}
