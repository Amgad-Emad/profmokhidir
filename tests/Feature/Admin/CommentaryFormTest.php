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
            ->assertSee('x-data="formEditor(', false)
            ->assertSee('wire:ignore', false)
            ->assertSee('x-modelable="value" wire:model="form.tags"', false)
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

    #[Test]
    public function the_html_the_tiptap_toolbar_produces_is_kept_on_save_and_shown_on_the_public_page(): void
    {
        // Exactly what the editor emits for heading, bold, italic, list, quote and link (browser check).
        $tiptap = '<h2>A heading</h2><p><strong>bold words</strong> and <em>italic words</em></p>'
            .'<ul><li><p>first item</p></li><li><p>second item</p></li></ul>'
            .'<blockquote><p>A quoted <a target="_blank" rel="noopener" href="https://example.org/paper">line</a></p></blockquote>';

        Livewire::actingAs(User::factory()->editor()->create())->test(CommentaryForm::class)
            ->set('form.title', 'Toolbar output')
            ->set('form.body', $tiptap)
            ->set('form.status', 'published')
            ->call('save')
            ->assertHasNoErrors();

        $commentary = Commentary::query()->where('title->en', 'Toolbar output')->sole();

        foreach (['<h2>A heading</h2>', '<strong>bold words</strong>', '<em>italic words</em>', '<li><p>first item</p></li>', '<blockquote>', 'href="https://example.org/paper"'] as $fragment) {
            $this->assertStringContainsString($fragment, $commentary->body);
        }

        $this->get(route('commentary.show', $commentary->slug))->assertOk()
            ->assertSee('<h2 id="part-a-heading">A heading</h2>', false)
            ->assertSee('<strong>bold words</strong>', false)
            ->assertSee('<em>italic words</em>', false)
            ->assertSee('<blockquote>', false)
            ->assertSee('href="https://example.org/paper"', false);
    }
}
