<?php

namespace Tests\Feature\Notifications;

use App\Constants\TributeRelationship;
use App\Models\Tribute;
use App\Models\User;
use App\Notifications\TributeAwaitingReview;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TributeAwaitingReviewTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_mail_names_the_sender_quotes_an_excerpt_links_to_the_dashboard_and_hides_the_email(): void
    {
        $editor = User::factory()->editor()->create(['name' => 'Edna Editor']);
        $tribute = Tribute::factory()->pending()->relationship(TributeRelationship::Colleague)->create([
            'name' => 'Leila Mahmoud',
            'location' => 'Rome, Italy',
            'email' => 'leila@example.com',
            'message' => "A generous\n\ncolleague   and friend. ".str_repeat('word ', 80),
        ]);

        $notification = new TributeAwaitingReview($tribute);

        $this->assertInstanceOf(ShouldQueue::class, $notification);
        $this->assertSame(['mail'], $notification->via($editor));

        $mail = $notification->toMail($editor);
        $rendered = $mail->render()->toHtml();

        $this->assertSame('A tribute is waiting for review', $mail->subject);
        $this->assertSame(route('admin.dashboard'), $mail->actionUrl);
        $this->assertStringContainsString('Hello Edna Editor,', $rendered);
        $this->assertStringContainsString('From: Leila Mahmoud (Colleague · Rome, Italy)', $rendered);
        $this->assertStringContainsString('A generous colleague and friend.', $rendered);
        $this->assertStringContainsString('...', $rendered);
        $this->assertStringNotContainsString('leila@example.com', $rendered);
        $this->assertStringEndsWith('...', $notification->excerpt());
        $this->assertSame(TributeAwaitingReview::EXCERPT_LENGTH + 3, mb_strlen($notification->excerpt()));
        $this->assertSame(['tribute_id' => $tribute->id, 'name' => 'Leila Mahmoud'], $notification->toArray($editor));
    }

    #[Test]
    public function the_from_line_omits_an_empty_location(): void
    {
        $tribute = Tribute::factory()->pending()->relationship(TributeRelationship::Family)->create(['name' => 'Sara', 'location' => null, 'message' => 'Short.']);

        $rendered = (new TributeAwaitingReview($tribute))->toMail(User::factory()->admin()->create())->render()->toHtml();

        $this->assertStringContainsString('From: Sara (Family)', $rendered);
        $this->assertStringContainsString('"Short."', $rendered);
    }
}
