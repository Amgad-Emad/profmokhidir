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
        $minutes = (int) config('auth.passwords.'.config('fortify.passwords', 'users').'.expire', 60);
        $site = ['site' => config('app.name')];

        $mail = (new MailMessage)
            ->subject(__('You have been invited to :site', $site))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line($this->invitationLine())
            ->line(__('Choose a password with the link below to activate your account. For security the link can only be used once and expires after :minutes minutes; if it has expired, ask an administrator to resend the invitation.', ['minutes' => $minutes]))
            ->action(__('Set your password'), $this->url($notifiable));

        return $mail->line(__('If you were not expecting this invitation you can ignore this email; nothing happens until the link is used.'));
    }

    /**
     * The opening line naming the role, one whole sentence per role so each
     * language can phrase it in its own grammar.
     */
    public function invitationLine(): string
    {
        $site = ['site' => config('app.name')];

        return match ($this->role) {
            Roles::ADMIN => __('You have been invited to join the dashboard of :site as an administrator.', $site),
            Roles::EDITOR => __('You have been invited to join the dashboard of :site as an editor.', $site),
            null => __('You have been invited to join the dashboard of :site as a member of staff.', $site),
            default => __('You have been invited to join the dashboard of :site as :role.', $site + ['role' => mb_strtolower(Roles::labels()[$this->role] ?? $this->role)]),
        };
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
