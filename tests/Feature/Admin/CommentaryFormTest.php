<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Commentaries\Form as CommentaryForm;
use App\Models\Commentary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CommentaryFormTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_body_is_edited_as_rich_text_sanitised_on_save_and_tags_are_split(): void
    {
        $editor = User::factory()->editor()->create();

        Livewire::actingAs($editor)->test(CommentaryForm::class)
            ->assertSee('data-rich-text', false)
            ->assertSee('richText(\'form.body\')', false)
            ->set('form.title', 'On teaching')
            ->set('form.body', '<h2 id="a">Heading</h2><p>Text <em>em</em> <a href="https://example.org" onclick="x()">link</a></p><ul><li>One</li></ul><blockquote><p>Q</p></blockquote><img src="x">')
            ->set('form.tags', 'Teaching, mentoring , teaching,, Agronomy')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $commentary = Commentary::query()->sole();
        $this->assertSame('<h2>Heading</h2><p>Text <em>em</em> <a href="https://example.org" rel="noopener">link</a></p><ul><li>One</li></ul><blockquote><p>Q</p></blockquote>', $commentary->body);
        $this->assertSame(['Teaching', 'mentoring', 'Agronomy'], $commentary->tags);
        $this->assertSame('on-teaching', $commentary->slug);

        Livewire::actingAs($editor)->test(CommentaryForm::class, ['commentary' => $commentary])
            ->assertSet('form.tags', 'Teaching, mentoring, Agronomy')
            ->assertSet('form.body', $commentary->body);
    }
}
