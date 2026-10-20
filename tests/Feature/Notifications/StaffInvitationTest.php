<?php

namespace Tests\Feature\Notifications;

use App\Constants\Roles;
use App\Models\User;
use App\Notifications\StaffInvitation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StaffInvitationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_mail_carries_a_set_password_link_and_never_a_password(): void
    {
        $user = User::factory()->editor()->invited()->create(['name' => 'Edna Editor', 'email' => 'edna@example.com']);
        $notification = new StaffInvitation('secret-token', Roles::EDITOR);

        $this->assertInstanceOf(ShouldQueue::class, $notification);
        $this->assertSame(['mail'], $notification->via($user));

        $mail = $notification->toMail($user);
        $rendered = $mail->render()->toHtml();

        $this->assertSame('You have been invited to '.config('app.name'), $mail->subject);
        $this->assertSame(url(route('password.reset', ['token' => 'secret-token', 'email' => 'edna@example.com'], false)), $mail->actionUrl);
        $this->assertStringContainsString('Hello Edna Editor,', $rendered);
        $this->assertStringContainsString('as an editor', $rendered);
        $this->assertStringContainsString('Set your password', $rendered);
        $this->assertStringContainsString('expires after '.config('auth.passwords.users.expire').' minutes', $rendered);
        $this->assertStringNotContainsString('password:', mb_strtolower($rendered));
        $this->assertStringNotContainsString('two-factor', $rendered);
    }

    #[Test]
    public function administrators_are_told_about_two_factor_authentication(): void
    {
        $user = User::factory()->admin()->withoutTwoFactor()->invited()->create();
        $rendered = (new StaffInvitation('token', Roles::ADMIN))->toMail($user)->render()->toHtml();

        $this->assertStringContainsString('as an administrator', $rendered);
        $this->assertStringContainsString('two-factor authentication', $rendered);
    }
}
