<?php

namespace Tests\Feature\Locale;

use App\Constants\CommunityStatus;
use App\Models\User;
use App\Notifications\CommunityApplicationReviewed;
use App\Notifications\StaffInvitation;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Emails go out in the recipient's saved language (User implements HasLocalePreference):
 * Arabic ones read right to left with an Arabic-capable font stack and Arabic chrome
 * (greeting, salutation, the "trouble clicking" footer); English ones are unchanged.
 */
class ArabicMailTest extends TestCase
{
    use RefreshDatabase;

    /** Sends a password reset link for real (array mailer) and returns the HTML and the subject. */
    private function resetMailFor(User $user): array
    {
        $sent = [];
        Event::listen(MessageSent::class, function (MessageSent $event) use (&$sent): void {
            $sent[] = $event->message;
        });

        $this->assertSame(Password::RESET_LINK_SENT, Password::sendResetLink(['email' => $user->email]));
        $this->assertCount(1, $sent);

        return [$sent[0]->getHtmlBody(), $sent[0]->getSubject()];
    }

    #[Test]
    public function the_password_reset_email_reads_in_arabic_for_an_arabic_user(): void
    {
        [$html, $subject] = $this->resetMailFor(User::factory()->arabic()->create());

        $this->assertSame('إعادة تعيين كلمة المرور', $subject);
        $this->assertStringContainsString('lang="ar" dir="rtl"', $html);
        $this->assertStringContainsString('<body dir="rtl"', $html);
        $this->assertStringContainsString('تصلك هذه الرسالة لأننا تلقّينا طلبًا لإعادة تعيين كلمة المرور الخاصة بحسابك.', $html);
        $this->assertStringContainsString('مرحبًا!', $html);
        $this->assertStringContainsString('مع خالص التحية،', $html);
        $this->assertStringContainsString('فانسخ الرابط أدناه والصقه', $html);
        $this->assertStringContainsString('جميع الحقوق محفوظة.', $html);
        $this->assertStringContainsString("'IBM Plex Sans Arabic'", $html);
        $this->assertStringContainsString('text-align: right', $html);
        $this->assertStringNotContainsString('If you did not request a password reset', $html);
    }

    #[Test]
    public function the_password_reset_email_stays_english_for_an_english_user(): void
    {
        [$html, $subject] = $this->resetMailFor(User::factory()->create());

        $this->assertSame('Reset your password', $subject);
        $this->assertStringContainsString('lang="en" dir="ltr"', $html);
        $this->assertStringContainsString('You are receiving this email because we received a password reset request for your account.', $html);
        $this->assertStringContainsString('Regards,', $html);
        $this->assertStringContainsString("If you're having trouble clicking", $html);
        $this->assertStringContainsString('text-align: left', $html);
        $this->assertStringNotContainsString('IBM Plex Sans Arabic', $html);
    }

    #[Test]
    public function our_own_notifications_are_queued_in_the_recipients_language(): void
    {
        Notification::fake();

        $arabic = User::factory()->arabic()->create(['name' => 'Amina Hassan']);
        $english = User::factory()->create();

        $arabic->notify(new CommunityApplicationReviewed(CommunityStatus::Approved));
        $english->notify(new CommunityApplicationReviewed(CommunityStatus::Approved));

        Notification::assertSentTo($arabic, CommunityApplicationReviewed::class, function ($notification, array $channels, object $notifiable, ?string $locale): bool {
            $this->app->setLocale((string) $locale);
            $mail = $notification->toMail($notifiable);
            $this->app->setLocale('en');

            return $locale === 'ar'
                && $mail->subject === 'تمت الموافقة على طلب انضمامك إلى المجتمع العلمي'
                && $mail->greeting === 'مرحبًا Amina Hassan،'
                && $mail->actionText === 'الدخول إلى المجتمع العلمي';
        });

        Notification::assertSentTo($english, CommunityApplicationReviewed::class, fn ($notification, $channels, $notifiable, $locale): bool => $locale === 'en');
    }

    #[Test]
    public function the_staff_invitation_renders_right_to_left_in_arabic(): void
    {
        $user = User::factory()->arabic()->create(['name' => 'Edna Editor']);
        $notification = new StaffInvitation('token-123', 'editor');

        $this->app->setLocale('ar');
        $html = (string) $notification->toMail($user)->render();
        $this->app->setLocale('en');

        $this->assertStringContainsString('lang="ar" dir="rtl"', $html);
        $this->assertStringContainsString('مرحبًا Edna Editor،', $html);
        $this->assertStringContainsString('بصفة محرر.', $html);
        $this->assertStringContainsString('تعيين كلمة المرور', $html);
        $this->assertStringContainsString('<span class="break-all" dir="ltr"', $html);
    }

    #[Test]
    public function the_reset_password_notification_is_sent_in_the_saved_language(): void
    {
        Notification::fake();

        $user = User::factory()->arabic()->create();
        Password::sendResetLink(['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, fn ($notification, $channels, $notifiable, $locale): bool => $locale === 'ar');
    }
}
