<?php

namespace Tests\Feature\Community;

use App\Constants\ChatCopy;
use App\Models\ChatChannel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The chat composer and the report dialog never show the browser's own
 * validation bubble; the image description is optional.
 */
class ChatComposerValidationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_room_forms_turn_off_native_validation_and_the_description_is_optional(): void
    {
        ChatChannel::factory()->room()->create();
        $member = User::factory()->member()->create();

        $page = $this->actingAs($member)->get(route('community.chat'))->assertOk();
        $html = $page->getContent();

        $page->assertSee(ChatCopy::ATTACH_DESCRIPTION_PLACEHOLDER)
            ->assertSee('Add a description (optional)');
        $this->assertMatchesRegularExpression('/<form class="chat-composer"[^>]*\snovalidate\s/', $html);
        preg_match_all('/<form\b[^>]*>/', $html, $forms);
        $this->assertGreaterThan(1, count($forms[0]));
        $this->assertSame([], array_values(array_filter($forms[0], fn (string $tag): bool => ! str_contains($tag, ' novalidate'))));
        $this->assertDoesNotMatchRegularExpression('/class="chat-tray__alt"[^>]*\srequired[\s>]/', $html);
    }

    #[Test]
    public function sign_in_and_tribute_forms_turn_off_native_validation(): void
    {
        foreach ([route('login'), route('password.request'), route('tributes.create')] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            preg_match_all('/<form\b[^>]*>/', $html, $forms);
            $this->assertNotEmpty($forms[0], $url);
            $this->assertSame([], array_values(array_filter($forms[0], fn (string $tag): bool => ! str_contains($tag, ' novalidate'))), $url);
        }
    }
}
