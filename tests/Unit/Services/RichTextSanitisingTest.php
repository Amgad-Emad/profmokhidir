<?php

namespace Tests\Unit\Services;

use App\Services\CommentaryService;
use App\Services\ProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RichTextSanitisingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function commentary_bodies_are_reduced_to_the_allowed_html_on_save(): void
    {
        $service = $this->app->make(CommentaryService::class);
        $dirty = '<h2 class="x" onclick="alert(1)">Title</h2><script>alert(1)</script><p>Text <a href="javascript:evil()">bad</a> <a href="https://example.org" target="_blank">good</a></p><img src="x"><blockquote><p>Quote</p></blockquote>';

        $commentary = $service->create(['title' => 'Piece', 'body' => $dirty]);

        $this->assertSame('<h2>Title</h2><p>Text <a>bad</a> <a href="https://example.org" rel="noopener">good</a></p><blockquote><p>Quote</p></blockquote>', $commentary->body);

        $updated = $service->update($commentary, ['body' => '   ']);
        $this->assertNull($updated->body);
    }

    #[Test]
    public function the_full_biography_is_sanitised_on_save(): void
    {
        $service = $this->app->make(ProfileService::class);

        $profile = $service->save(['name' => 'Prof', 'full_bio' => '<p style="color:red">Bio<iframe src="x"></iframe></p>']);

        $this->assertSame('<p>Bio</p>', $profile->full_bio);
    }
}
