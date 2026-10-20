<?php

namespace Tests\Feature\Notifications;

use App\Constants\AdminNavigation;
use App\Constants\SettingKeys;
use App\Models\User;
use App\Notifications\CommunityApplicationReceived;
use App\Services\CommunityService;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CommunityApplicationReceivedTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_new_application_is_announced_to_every_admin_when_no_notification_address_is_set(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $editor = User::factory()->editor()->create();

        $applicant = app(CommunityService::class)->register(['name' => 'Omar', 'email' => 'omar@example.com', 'password' => 'password-1234', 'affiliation' => 'University of Khartoum']);

        Notification::assertSentTo($admin, CommunityApplicationReceived::class, fn (CommunityApplicationReceived $n): bool => $n->applicant->is($applicant));
        Notification::assertNotSentTo($editor, CommunityApplicationReceived::class);
    }

    #[Test]
    public function the_notification_address_takes_precedence_and_the_mail_links_to_the_queue(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        app(SettingService::class)->update([SettingKeys::NOTIFICATION_EMAIL => 'notices@example.com']);

        $applicant = app(CommunityService::class)->register(['name' => 'Omar Siddig', 'email' => 'omar@example.com', 'password' => 'password-1234', 'affiliation' => 'University of Khartoum', 'academic_field' => 'Agronomy']);

        Notification::assertSentOnDemand(CommunityApplicationReceived::class, fn (CommunityApplicationReceived $n, array $channels, AnonymousNotifiable $notifiable): bool => $notifiable->routes['mail'] === 'notices@example.com');
        Notification::assertNotSentTo($admin, CommunityApplicationReceived::class);

        $mail = (new CommunityApplicationReceived($applicant))->toMail(new AnonymousNotifiable);
        $rendered = $mail->render()->toHtml();

        $this->assertSame('A community application is waiting for review', $mail->subject);
        $this->assertSame(route(AdminNavigation::routeFor(AdminNavigation::APPLICATIONS)), $mail->actionUrl);
        $this->assertStringContainsString('Hello,', $rendered);
        $this->assertStringContainsString('Omar Siddig has applied', $rendered);
        $this->assertStringContainsString('University of Khartoum · Agronomy', $rendered);
    }
}
