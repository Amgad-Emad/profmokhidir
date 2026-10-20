<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Constants\AdminNavigation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the notification address (or, when none is set, every administrator)
 * that a Scientific Community application is waiting for review.
 */
class CommunityApplicationReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly User $applicant) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $details = array_filter([$this->applicant->affiliation, $this->applicant->academic_field]);

        return (new MailMessage)
            ->subject('A community application is waiting for review')
            ->greeting($notifiable instanceof User ? 'Hello '.$notifiable->name.',' : 'Hello,')
            ->line($this->applicant->name.' has applied to join the Scientific Community on '.config('app.name').'.')
            ->line($details === [] ? 'No affiliation or field was given.' : implode(' · ', $details))
            ->action('Review applications', route(AdminNavigation::routeFor(AdminNavigation::APPLICATIONS)))
            ->line('They cannot use the chat until an administrator approves them.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return ['user_id' => $this->applicant->getKey(), 'name' => $this->applicant->name];
    }
}
