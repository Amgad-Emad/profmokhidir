<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Tribute;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Tells an admin or editor that a visitor's tribute is waiting for review.
 * The visitor's email is deliberately left out of the message.
 */
class TributeAwaitingReview extends Notification implements ShouldQueue
{
    use Queueable;

    public const int EXCERPT_LENGTH = 200;

    public function __construct(public readonly Tribute $tribute) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tribute = $this->tribute;
        $from = implode(' · ', array_filter([$tribute->relationship?->label(), $tribute->location]));

        return (new MailMessage)
            ->subject('A tribute is waiting for review')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('A new tribute has been submitted on '.config('app.name').' and is waiting for review.')
            ->line('From: '.$tribute->name.($from !== '' ? ' ('.$from.')' : ''))
            ->line('"'.$this->excerpt().'"')
            ->action('Open the dashboard', route('admin.dashboard'))
            ->line('It will not appear on the site until it has been approved.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tribute_id' => $this->tribute->getKey(),
            'name' => $this->tribute->name,
        ];
    }

    public function excerpt(): string
    {
        return Str::limit(trim(preg_replace('/\s+/u', ' ', $this->tribute->message) ?? ''), self::EXCERPT_LENGTH);
    }
}
