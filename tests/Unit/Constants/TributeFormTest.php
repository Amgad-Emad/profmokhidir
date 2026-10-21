<?php

namespace Tests\Unit\Constants;

use App\Constants\TributeForm;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TributeFormTest extends TestCase
{
    #[Test]
    public function the_flow_has_two_steps_in_order(): void
    {
        $this->assertSame(['write' => 'Write', 'preview' => 'Preview'], TributeForm::steps());
        $this->assertSame(1, TributeForm::position(TributeForm::STEP_WRITE));
        $this->assertSame(2, TributeForm::position(TributeForm::STEP_PREVIEW));
        $this->assertSame(1, TributeForm::position('unknown'));
    }

    #[Test]
    public function every_field_has_a_label_and_a_helpful_hint(): void
    {
        $this->assertSame(['name', 'relationship', 'location', 'message', 'email'], array_keys(TributeForm::fields()));

        foreach (TributeForm::fields() as $field => $copy) {
            $this->assertNotSame('', trim($copy['label']), $field);
            $this->assertNotSame('', trim($copy['hint']), $field);
            $this->assertSame($copy['label'], TributeForm::label($field));
            $this->assertSame($copy['hint'], TributeForm::hint($field));
        }

        $this->assertStringContainsString('Share a memory, a lesson or a moment', TributeForm::hint(TributeForm::FIELD_MESSAGE));
        $this->assertStringContainsString('never shown publicly', TributeForm::hint(TributeForm::FIELD_EMAIL));
        $this->assertStringContainsString('read before it is published', TributeForm::INTRO);
    }
}
