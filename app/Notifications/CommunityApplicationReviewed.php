<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Constants\CommunityStatus;
use App\Constants\Sections;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells an applicant that their community application was approved or
 * rejected (with the reviewer's optional note), or that their membership was
 * suspended, reinstated or ended.
 */
class CommunityApplicationReviewed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly CommunityStatus $status,
        public readonly ?string $note = null,
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
        $communityUrl = route(Sections::definitions()[Sections::COMMUNITY]['route']);
        $mail = (new MailMessage)
            ->subject($this->subject())
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]));

        foreach ($this->lines() as $line) {
            $mail->line($line);
        }

        if (filled($this->note)) {
            $mail->line(__('Note from the reviewer: :note', ['note' => $this->note]));
        }

        return $mail->action($this->status === CommunityStatus::Approved ? __('Open the community') : __('Visit the community page'), $communityUrl);
    }

    public function subject(): string
    {
        return match ($this->status) {
            CommunityStatus::Approved => __('Your community application has been approved'),
            CommunityStatus::Rejected => __('About your community application'),
            CommunityStatus::Suspended => __('Your community membership has been suspended'),
            CommunityStatus::None => __('Your community membership has ended'),
            CommunityStatus::Pending => __('Your community application has been received'),
        };
    }

    /**
     * @return list<string>
     */
    public function lines(): array
    {
        $site = ['site' => config('app.name')];

        return match ($this->status) {
            CommunityStatus::Approved => [
                __('Your application to join the Scientific Community on :site has been approved.', $site),
                __('You can now sign in and take part in the members\' chat.'),
            ],
            CommunityStatus::Rejected => [
                __('Thank you for applying to join the Scientific Community on :site.', $site),
                __('On this occasion your application has not been accepted.'),
            ],
            CommunityStatus::Suspended => [
                __('Your membership of the Scientific Community on :site has been suspended by an administrator, and the chat is closed to you for now.', $site),
            ],
            CommunityStatus::None => [
                __('Your membership of the Scientific Community on :site has ended.', $site),
                __('You are welcome to apply again.'),
            ],
            CommunityStatus::Pending => [
                __('Your application to join the Scientific Community on :site has been received and will be reviewed by an administrator.', $site),
            ],
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return ['status' => $this->status->value, 'note' => $this->note];
    }
}
