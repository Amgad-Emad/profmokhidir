<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Constants\AdminNavigation;
use App\Constants\Roles;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Invites a new administrator or editor to set their password. The link is a
 * password-broker token (the same one "forgot password" uses), so no password
 * ever travels by email. Two-factor authentication is not part of the
 * invitation: the dashboard recommends it once they are signed in.
 */
class StaffInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $token,
        public readonly ?string $role = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $role = $this->role === null ? 'a member of staff' : 'an '.mb_strtolower(Roles::labels()[$this->role] ?? $this->role);
        $minutes = (int) config('auth.passwords.'.config('fortify.passwords', 'users').'.expire', 60);

        $mail = (new MailMessage)
            ->subject('You have been invited to '.config('app.name'))
            ->greeting('Hello '.$notifiable->name.',')
            ->line('You have been invited to join the dashboard of '.config('app.name').' as '.$role.'.')
            ->line('Choose a password with the link below to activate your account. For security the link can only be used once and expires after '.$minutes.' minutes; if it has expired, ask an administrator to resend the invitation.')
            ->action('Set your password', $this->url($notifiable));

        return $mail->line('If you were not expecting this invitation you can ignore this email; nothing happens until the link is used.');
    }

    public function url(object $notifiable): string
    {
        return url(route('password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()], false));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return ['role' => $this->role, 'account' => route(AdminNavigation::ACCOUNT_ROUTE)];
    }
}
